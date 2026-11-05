<?php

namespace App\Controllers;

use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Restaurant;
use App\Services\AuthService;
use App\Services\OrderService;

/**
 * Customer order pages (Module 5): history, detail with the live
 * status timeline (unique feature), the JSON polling endpoint, and
 * cancellation. Customers only ever see their OWN orders.
 */
class OrderController
{
    private function customerId(): int
    {
        return (int) AuthService::user()['id'];
    }

    public function index(): void
    {
        view('orders/index', [
            'title'  => 'My Orders',
            'orders' => Order::listForCustomer($this->customerId()),
        ]);
    }

    public function show(string $id): void
    {
        $order = $this->ownOrder((int) $id);

        view('orders/show', [
            'title'       => 'Order ' . $order['order_no'],
            'order'       => $order,
            'items'       => OrderItem::allForOrder((int) $order['id']),
            'history'     => OrderStatusHistory::allForOrder((int) $order['id']),
            'restaurant'  => Restaurant::find((int) $order['restaurant_id']),
            'branch'      => !empty($order['branch_id']) ? Branch::find((int) $order['branch_id']) : null,
            'canCancel'   => in_array($order['status'], ['pending_payment', 'confirmed'], true),
        ]);
    }

    /**
     * ★ JSON endpoint polled by the detail page every few seconds —
     * the page reloads itself when the status changes (live tracking
     * without WebSockets).
     */
    public function status(string $id): void
    {
        $order = $this->ownOrder((int) $id, true);

        header('Content-Type: application/json');
        echo json_encode([
            'status'     => $order['status'],
            'updated_at' => $order['updated_at'],
        ]);
        exit;
    }

    /** Customer cancels their own order before preparation starts. */
    public function cancel(string $id): void
    {
        $order = $this->ownOrder((int) $id);

        if (!OrderService::transition((int) $order['id'], 'cancelled', AuthService::user(), 'Cancelled by customer')) {
            redirect('/orders/' . (int) $order['id']);
        }

        flash('success', 'Order cancelled.');
        redirect('/orders/' . (int) $order['id']);
    }

    /** Ownership check: the order must belong to the session's customer. */
    private function ownOrder(int $id, bool $json = false): array
    {
        $order = Order::find($id);

        if ($order === null || (int) $order['customer_id'] !== $this->customerId()) {
            if ($json) {
                http_response_code(404);
                echo json_encode(['error' => 'not_found']);
                exit;
            }
            abort(404);
        }

        return $order;
    }
}
