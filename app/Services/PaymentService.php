<?php

namespace App\Services;

use App\Models\Fulfilment;
use App\Models\Order;
use App\Models\Payment;

/**
 * Payment business logic (Module 6) — the standard payment
 * verification simulation workflow:
 *
 *   1. customer selects a payment method (cash / online)
 *   2. the system creates a payment record (pending)
 *   3. the simulated gateway returns success or failure
 *   4. the payment status is updated
 *   5. SUCCESS advances the order (pending_payment → confirmed) and
 *      creates the fulfilment record
 *   6. FAILURE never marks the order as paid — the customer may retry
 *
 * No card numbers or CVVs are stored anywhere. Completed financial
 * records keep their audit trail and are never physically deleted.
 */
class PaymentService
{
    /**
     * Process a (simulated) payment for the customer's own order.
     * Online payments can be forced to fail with $simulateDecline —
     * a demo control so both paths are reproducible.
     */
    public static function pay(int $orderId, int $customerId, string $method, bool $simulateDecline): bool
    {
        if (!in_array($method, ['cash', 'online'], true)) {
            flash('error', 'Please choose a valid payment method.');
            return false;
        }

        $order = Order::find($orderId);
        if ($order === null || (int) $order['customer_id'] !== $customerId) {
            flash('error', 'Order not found.');
            return false;
        }

        if ($order['status'] !== 'pending_payment') {
            flash('error', 'This order is not awaiting payment.');
            return false;
        }

        $payment = Payment::findByOrder($orderId);

        // Duplicate-success protection: an order can be paid only once.
        if ($payment !== null && $payment['status'] === 'success') {
            flash('error', 'This order has already been paid.');
            return false;
        }

        if ($payment !== null && $payment['status'] === 'void') {
            flash('error', 'This payment was voided and cannot be retried.');
            return false;
        }

        if ($payment === null) {
            $paymentId = Payment::create($orderId, $method, (float) $order['total']);
        } else {
            // Retry of a failed/pending attempt: reuse the record.
            $paymentId = (int) $payment['id'];
            Payment::reset($paymentId, $method, (float) $order['total']);
        }

        activity('payment_create', 'payment', $paymentId, 'method=' . $method);

        // Simulated gateway: cash always succeeds; online can decline
        // when the demo control is selected.
        $success = $method === 'cash' || !$simulateDecline;

        if (!$success) {
            Payment::markFailed($paymentId);
            activity('payment_failed', 'payment', $paymentId, 'Simulated gateway declined');
            flash('error', 'Payment failed (simulated). Please try again.');
            return false;
        }

        $reference = 'SIM-' . strtoupper(bin2hex(random_bytes(4)));
        Payment::markSuccess($paymentId, $reference);
        activity('payment_success', 'payment', $paymentId, $reference);

        // 5. Successful payment advances the order via the validated
        // transition map (system actor — the payment workflow itself).
        OrderService::transitionBySystem(
            $orderId,
            'confirmed',
            'Paid via ' . ($method === 'online' ? 'online payment' : 'cash')
        );

        // Fulfilment record: delivery or self-pickup execution details.
        $fulfilmentId = Fulfilment::create(
            $orderId,
            $order['fulfilment_type'],
            $order['fulfilment_type'] === 'delivery' ? $order['delivery_address'] : null,
            $order['fulfilment_type'] === 'delivery' ? $order['delivery_phone'] : null
        );
        activity('fulfilment_create', 'fulfilment', $fulfilmentId);

        // Auto-accept (merchant setting): restaurants with auto_accept
        // skip the manual Accept step — the order moves straight to
        // preparing via the same validated transition map.
        $restaurant = \App\Models\Restaurant::find((int) $order['restaurant_id']);
        if ($restaurant !== null && (int) $restaurant['auto_accept'] === 1) {
            OrderService::transitionBySystem(
                $orderId,
                'preparing',
                'Auto-accepted by restaurant setting'
            );
        }

        return true;
    }

    /** Void a successful payment (refund/audit) — records are kept. */
    public static function markVoid(int $paymentId, int $tenantId): bool
    {
        $payment = self::ownedPayment($paymentId, $tenantId);
        if ($payment === null) {
            return false;
        }

        if ($payment['status'] !== 'success') {
            flash('error', 'Only successful payments can be voided.');
            return false;
        }

        Payment::markVoid($paymentId);
        activity('payment_void', 'payment', $paymentId);

        return true;
    }

    /** Delete an eligible pending/failed TEST record (Module 6 DELETE). */
    public static function delete(int $paymentId, int $tenantId): bool
    {
        $payment = self::ownedPayment($paymentId, $tenantId);
        if ($payment === null) {
            return false;
        }

        // Audit trail: completed financial records are never deleted.
        if (in_array($payment['status'], ['success', 'void'], true)) {
            flash('error', 'Completed payment records cannot be deleted — they are kept for audit. Use void instead.');
            return false;
        }

        Payment::delete($paymentId);
        activity('payment_delete', 'payment', $paymentId);

        return true;
    }

    /** Ownership check: the payment's order must belong to the tenant. */
    private static function ownedPayment(int $paymentId, int $tenantId): ?array
    {
        $payment = Payment::find($paymentId);
        if ($payment === null) {
            flash('error', 'Payment record not found.');
            return null;
        }

        $order = Order::find((int) $payment['order_id']);
        if ($order === null || (int) $order['restaurant_id'] !== $tenantId) {
            flash('error', 'Payment record not found.');
            return null;
        }

        return $payment;
    }
}
