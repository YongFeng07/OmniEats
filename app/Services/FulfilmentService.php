<?php

namespace App\Services;

use App\Models\Fulfilment;
use App\Models\Order;

/**
 * Fulfilment business logic (Module 6): delivery / self-pickup
 * execution records with validated status transitions.
 */
class FulfilmentService
{
    /** Transitions per current status; allowed targets depend on type. */
    public const TRANSITIONS = [
        'pending'               => [],                    // depends on type
        'out_for_delivery'      => ['completed', 'cancelled'],
        'ready_for_collection'  => ['completed', 'cancelled'],
        'completed'             => [],
        'cancelled'             => [],
    ];

    /** Update a fulfilment's status (tenant-scoped, validated). */
    public static function updateStatus(int $fulfilmentId, int $tenantId, string $status): bool
    {
        $fulfilment = Fulfilment::find($fulfilmentId);
        if ($fulfilment === null) {
            flash('error', 'Fulfilment record not found.');
            return false;
        }

        $order = Order::find((int) $fulfilment['order_id']);
        if ($order === null || (int) $order['restaurant_id'] !== $tenantId) {
            flash('error', 'Fulfilment record not found.');
            return false;
        }

        $current = $fulfilment['status'];

        // Which "start" status applies depends on the fulfilment type.
        if ($current === 'pending') {
            $allowed = $fulfilment['type'] === 'delivery'
                ? ['out_for_delivery', 'cancelled']
                : ['ready_for_collection', 'cancelled'];
        } else {
            $allowed = self::TRANSITIONS[$current] ?? [];
        }

        if (!in_array($status, $allowed, true)) {
            flash('error', 'This fulfilment status change is not allowed (from ' . $current . ' to ' . $status . ').');
            return false;
        }

        Fulfilment::setStatus($fulfilmentId, $status);

        activity('fulfilment_' . $status, 'fulfilment', $fulfilmentId);

        return true;
    }

    /** Called when an order is cancelled (M5 integration). */
    public static function cancelForOrder(int $orderId): void
    {
        $fulfilment = Fulfilment::findByOrder($orderId);
        if ($fulfilment !== null && $fulfilment['status'] !== 'completed') {
            Fulfilment::setStatus((int) $fulfilment['id'], 'cancelled');
        }
    }

    /**
     * M6 DELETE — eligible fulfilment records only: pending (test data)
     * or cancelled. Completed / out_for_delivery / ready_for_collection
     * records are execution history and are never physically deleted.
     * Tenant-scoped through the order.
     */
    public static function delete(int $fulfilmentId, int $tenantId): bool
    {
        $fulfilment = Fulfilment::find($fulfilmentId);
        if ($fulfilment === null) {
            flash('error', 'Fulfilment record not found.');
            return false;
        }

        $order = Order::find((int) $fulfilment['order_id']);
        if ($order === null || (int) $order['restaurant_id'] !== $tenantId) {
            flash('error', 'Fulfilment record not found.');
            return false;
        }

        if (!in_array($fulfilment['status'], ['pending', 'cancelled'], true)) {
            flash('error', 'Only pending or cancelled fulfilment records can be deleted — completed records are kept for audit.');
            return false;
        }

        Fulfilment::delete($fulfilmentId);
        activity('fulfilment_delete', 'fulfilment', $fulfilmentId);

        return true;
    }
}
