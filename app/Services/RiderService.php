<?php

namespace App\Services;

use App\Models\Fulfilment;
use App\Models\Order;
use App\Models\RiderAssignmentHistory;
use App\Models\RiderProfile;
use App\Models\User;

/**
 * Delivery & Rider business logic (Student 3 — Leong Hong Jun).
 *
 * ★ UNIQUE FEATURE — ADMIN MANUAL RIDER ASSIGNMENT:
 * Only the Super Admin assigns/reassigns riders. Every attempt is
 * recorded in rider_assignment_history (rejections and reassignments
 * included — history is never overwritten). One active delivery per
 * rider is enforced here with a database transaction.
 */
class RiderService
{
    /**
     * Admin manually assigns an eligible rider to a delivery.
     * Re-assignment is the same call: the previous attempt is marked
     * "Reassigned" and a new history row records the new rider.
     */
    public static function assign(int $fulfilmentId, int $riderId, int $adminId): bool
    {
        $fulfilment = Fulfilment::find($fulfilmentId);
        if ($fulfilment === null || $fulfilment['type'] !== 'delivery') {
            flash('error', 'Delivery record not found.');
            return false;
        }

        $order = Order::find((int) $fulfilment['order_id']);
        if ($order === null || $order['status'] !== 'ready') {
            flash('error', 'Only deliveries of READY orders can be assigned.');
            return false;
        }

        if (!in_array($fulfilment['status'], ['pending_assignment', 'assigned'], true)) {
            flash('error', 'This delivery is not awaiting assignment (current: ' . $fulfilment['status'] . ').');
            return false;
        }

        // Rider eligibility: active account, active profile, available,
        // and no other active delivery (MVP rule: one at a time).
        $riderUser = User::find($riderId);
        if ($riderUser === null || (int) $riderUser['is_active'] !== 1) {
            flash('error', 'This rider account is inactive.');
            return false;
        }
        $profile = RiderProfile::findForUser($riderId);
        if ($profile === null || (int) $profile['is_active'] !== 1) {
            flash('error', 'This rider is suspended.');
            return false;
        }
        if ((int) $profile['is_available'] !== 1) {
            flash('error', 'This rider is currently unavailable.');
            return false;
        }
        $active = Fulfilment::activeForRider($riderId);
        if ($active !== null && (int) $active['id'] !== $fulfilmentId) {
            flash('error', 'This rider already has an active delivery and cannot take another one.');
            return false;
        }

        // Transaction: history + assignment update together.
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $previous = RiderAssignmentHistory::latestForFulfilment($fulfilmentId);
            if ($previous !== null && $previous['status'] !== 'Rejected') {
                RiderAssignmentHistory::recordResponse((int) $previous['id'], 'Reassigned');
            }

            RiderAssignmentHistory::create($fulfilmentId, $riderId, $adminId, 'Assigned');
            Fulfilment::assignRider($fulfilmentId, $riderId, $adminId);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            flash('error', 'The assignment could not be saved. Please try again.');
            return false;
        }

        activity('rider_assign', 'fulfilment', $fulfilmentId, 'rider=' . $riderId);
        return true;
    }

    /** Rider accepts or rejects their own assigned delivery. */
    public static function respond(int $fulfilmentId, int $riderId, string $action, ?string $reason = null): bool
    {
        $fulfilment = Fulfilment::find($fulfilmentId);
        if ($fulfilment === null
            || (int) $fulfilment['rider_id'] !== $riderId
            || $fulfilment['status'] !== 'assigned') {
            flash('error', 'This delivery is not assigned to you.');
            return false;
        }

        $history = RiderAssignmentHistory::latestForFulfilment($fulfilmentId);

        if ($action === 'accept') {
            Fulfilment::markAccepted($fulfilmentId);
            if ($history !== null) {
                RiderAssignmentHistory::recordResponse((int) $history['id'], 'Accepted');
            }
            activity('rider_accept', 'fulfilment', $fulfilmentId);
            return true;
        }

        if ($action === 'reject') {
            $reason = trim((string) $reason);
            if ($reason === '') {
                flash('error', 'Please provide a reason for rejecting the assignment.');
                return false;
            }
            if ($history !== null) {
                RiderAssignmentHistory::recordResponse((int) $history['id'], 'Rejected', $reason);
            }
            Fulfilment::clearAssignment($fulfilmentId);
            activity('rider_reject', 'fulfilment', $fulfilmentId, $reason);
            return true;
        }

        flash('error', 'Invalid action.');
        return false;
    }

    /**
     * Rider advances their own delivery: pickup → out_for_delivery →
     * delivered. Every step is validated in order; delivery completes
     * the order automatically through the validated order transitions.
     */
    public static function progress(int $fulfilmentId, int $riderId, string $step): bool
    {
        $fulfilment = Fulfilment::find($fulfilmentId);
        if ($fulfilment === null
            || (int) $fulfilment['rider_id'] !== $riderId) {
            flash('error', 'This delivery is not assigned to you.');
            return false;
        }

        $current = $fulfilment['status'];

        if ($step === 'pickup' && $current === 'accepted') {
            Fulfilment::markPickedUp($fulfilmentId);
            activity('rider_pickup', 'fulfilment', $fulfilmentId);
            return true;
        }

        if ($step === 'out_for_delivery' && $current === 'picked_up') {
            Fulfilment::markOutForDelivery($fulfilmentId);
            activity('rider_out_for_delivery', 'fulfilment', $fulfilmentId);
            return true;
        }

        if ($step === 'delivered' && $current === 'out_for_delivery') {
            $pdo = db();
            $pdo->beginTransaction();
            try {
                Fulfilment::markDelivered($fulfilmentId);

                // The delivery completes the ORDER through the validated
                // transition map (system actor) — ready → completed.
                OrderService::transitionBySystem(
                    (int) $fulfilment['order_id'],
                    'completed',
                    'Delivered by rider'
                );

                // COD: cash payment already marked success at payment
                // time in this system's simulation — nothing further.

                $pdo->commit();
            } catch (\Throwable $e) {
                $pdo->rollBack();
                flash('error', 'The delivery could not be completed. Please try again.');
                return false;
            }

            activity('rider_delivered', 'fulfilment', $fulfilmentId);
            return true;
        }

        flash('error', 'This delivery step is not allowed (from ' . $current . ' to ' . $step . ').');
        return false;
    }

    /**
     * Admin creates a rider account: users row + rider role +
     * rider_profiles row, in one transaction. No public rider
     * self-registration exists by design.
     */
    public static function createRider(array $data): bool
    {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $userId = User::create(
                trim((string) $data['name']),
                trim((string) $data['email']),
                password_hash((string) $data['password'], PASSWORD_DEFAULT),
                trim((string) ($data['phone'] ?? '')),
                null
            );

            $role = \App\Models\Role::findByName('rider');
            if ($role !== null) {
                // addRole (not setRoles) — we are already inside a
                // transaction here and setRoles opens its own.
                User::addRole($userId, (int) $role['id']);
            }

            RiderProfile::create(
                $userId,
                trim((string) ($data['vehicle_type'] ?? '')),
                trim((string) ($data['vehicle_plate'] ?? ''))
            );
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            flash('error', 'The rider account could not be created. Please try again.');
            return false;
        }

        activity('create_rider', 'user', $userId, $data['email']);
        return true;
    }

    /** Suspend / reactivate a rider (rider DELETE — records preserved). */
    public static function setSuspended(int $profileId, bool $suspended): bool
    {
        $profile = RiderProfile::find($profileId);
        if ($profile === null) {
            flash('error', 'Rider not found.');
            return false;
        }

        // A suspended rider loses any pending assignment first.
        if ($suspended) {
            $active = Fulfilment::activeForRider((int) $profile['user_id']);
            if ($active !== null) {
                Fulfilment::clearAssignment((int) $active['id']);
            }
        }

        RiderProfile::setActive($profileId, !$suspended);
        activity($suspended ? 'rider_suspend' : 'rider_reactivate', 'rider_profile', $profileId);
        return true;
    }

    /** Rider toggles their own availability (on/off shift). */
    public static function setAvailability(int $profileId, bool $available): bool
    {
        $profile = RiderProfile::find($profileId);
        if ($profile === null) {
            flash('error', 'Rider not found.');
            return false;
        }
        if ((int) $profile['is_active'] !== 1) {
            flash('error', 'A suspended rider cannot change availability.');
            return false;
        }

        RiderProfile::setAvailable($profileId, $available);
        return true;
    }
}
