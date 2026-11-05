<?php

namespace App\Controllers\Owner;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\AuthService;
use App\Services\PaymentService;

/**
 * Restaurant payment management (Module 6) — owner and staff for
 * viewing; void/delete are owner-only (route guards).
 */
class PaymentController
{
    private function tenantId(): ?int
    {
        $restaurantId = AuthService::user()['restaurant_id'] ?? null;
        return $restaurantId !== null ? (int) $restaurantId : null;
    }

    public function index(): void
    {
        $restaurantId = $this->tenantId();
        if ($restaurantId === null) {
            redirect('/owner');
        }

        $status = in_array($_GET['status'] ?? '', ['pending', 'success', 'failed', 'void'], true)
            ? $_GET['status'] : '';

        view('owner/payments/index', [
            'title'    => 'Payments',
            'payments' => Payment::listForRestaurant($restaurantId, $status !== '' ? $status : null),
            'fStatus'  => $status,
        ]);
    }

    public function show(string $id): void
    {
        $payment = $this->ownedPayment((int) $id);

        $order = Order::find((int) $payment['order_id']);
        $customer = User::find((int) $order['customer_id']);

        view('owner/payments/show', [
            'title'    => 'Payment ' . $payment['transaction_ref'] ?? ('#' . $payment['id']),
            'payment'  => $payment,
            'order'    => $order,
            'customer' => $customer,
        ]);
    }

    /** Void a successful payment (refund/audit) — owner only. */
    public function void(string $id): void
    {
        if (!PaymentService::markVoid((int) $id, $this->tenantId())) {
            redirect('/owner/payments/' . (int) $id);
        }

        flash('success', 'Payment voided. The record is preserved for audit.');
        redirect('/owner/payments/' . (int) $id);
    }

    /** Delete an eligible pending/failed test record — owner only. */
    public function delete(string $id): void
    {
        if (!PaymentService::delete((int) $id, $this->tenantId())) {
            redirect('/owner/payments');
        }

        flash('success', 'Payment record deleted.');
        redirect('/owner/payments');
    }

    private function ownedPayment(int $id): array
    {
        $payment = Payment::find($id);
        if ($payment === null) {
            abort(404);
        }

        $order = Order::find((int) $payment['order_id']);
        if ($order === null || (int) $order['restaurant_id'] !== $this->tenantId()) {
            abort(404);
        }

        return $payment;
    }
}
