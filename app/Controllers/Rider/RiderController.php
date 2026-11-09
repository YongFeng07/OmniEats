<?php

namespace App\Controllers\Rider;

use App\Models\Fulfilment;
use App\Models\Order;
use App\Models\RiderProfile;
use App\Services\AuthService;
use App\Services\RiderService;

/**
 * Rider Portal (Delivery & Rider module — Student 3).
 * Riders see ONLY their own assignments: accept/reject, pickup
 * confirmation, out-for-delivery, delivery completion, history and
 * their own availability. Assignment itself is admin-only.
 */
class RiderController
{
    private function riderId(): int
    {
        return (int) AuthService::user()['id'];
    }

    private function profileRow(): array
    {
        return RiderProfile::findForUser($this->riderId()) ?? [];
    }

    public function dashboard(): void
    {
        $active = Fulfilment::activeForRider($this->riderId());

        view('rider/dashboard', [
            'title'   => 'Rider Dashboard',
            'profile' => $this->profileRow(),
            'active'  => $active,
        ]);
    }

    /** Assigned tasks awaiting the rider's response or action. */
    public function tasks(): void
    {
        $active = Fulfilment::activeForRider($this->riderId());
        $order = $active !== null ? Order::find((int) $active['order_id']) : null;

        view('rider/tasks', [
            'title'   => 'My Deliveries',
            'profile' => $this->profileRow(),
            'active'  => $active,
            'order'   => $order,
        ]);
    }

    /** Accept or reject an assignment that is assigned to this rider. */
    public function respond(string $id): void
    {
        $action = (string) ($_POST['action'] ?? '');
        $reason = (string) ($_POST['reason'] ?? '');

        if (RiderService::respond((int) $id, $this->riderId(), $action, $reason)) {
            flash('success', $action === 'accept'
                ? 'Assignment accepted — head to the restaurant for pickup.'
                : 'Assignment rejected — the delivery has been returned to the administrator.');
        }

        redirect('/rider/tasks');
    }

    /** Delivery progress: pickup → out_for_delivery → delivered. */
    public function progress(string $id): void
    {
        $step = (string) ($_POST['step'] ?? '');

        if (RiderService::progress((int) $id, $this->riderId(), $step)) {
            flash('success', match ($step) {
                'pickup'          => 'Pickup confirmed — drive safely!',
                'out_for_delivery' => 'You are out for delivery.',
                'delivered'       => 'Delivery completed. The customer sees the final status now.',
                default           => 'Updated.',
            });
        }

        redirect('/rider/tasks');
    }

    public function history(): void
    {
        view('rider/history', [
            'title'   => 'Delivery History',
            'profile' => $this->profileRow(),
            'history' => Fulfilment::historyForRider($this->riderId()),
        ]);
    }

    /** Own profile: vehicle info (admin-managed) + availability toggle. */
    public function profile(): void
    {
        view('rider/profile', [
            'title'   => 'My Rider Profile',
            'profile' => $this->profileRow(),
            'user'    => AuthService::user(),
        ]);
    }

    public function toggleAvailable(): void
    {
        $profile = $this->profileRow();
        if ($profile === []) {
            flash('error', 'Rider profile not found.');
            redirect('/rider');
        }

        $available = (int) $profile['is_available'] !== 1;
        if (RiderService::setAvailability((int) $profile['id'], $available)) {
            flash('success', $available
                ? 'You are now AVAILABLE — the administrator can assign deliveries to you.'
                : 'You are now UNAVAILABLE — no new assignments will come.');
        }

        redirect('/rider');
    }
}
