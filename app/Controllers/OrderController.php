<?php

namespace App\Controllers;

use App\Models\Branch;
use App\Models\Fulfilment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Models\Review;
use App\Services\AuthService;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\ReviewService;

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
        $showArchived = ($_GET['show'] ?? '') === 'archived';

        view('orders/index', [
            'title'        => 'My Orders',
            'orders'       => Order::listForCustomer($this->customerId(), $showArchived),
            'showArchived' => $showArchived,
        ]);
    }

    /**
     * M5 DELETE (soft): the customer hides/unhides a terminal order
     * (completed / cancelled) in their order list. The record itself is
     * never deleted — it stays in the audit trail.
     */
    public function archive(string $id): void
    {
        $order = $this->ownOrder((int) $id);

        if (!in_array($order['status'], ['completed', 'cancelled'], true)) {
            flash('error', 'Only completed or cancelled orders can be archived.');
            redirect('/orders/' . (int) $order['id']);
        }

        $archived = (int) $order['is_archived'] === 0;
        Order::setArchived((int) $order['id'], $archived);
        activity($archived ? 'archive_order' : 'unarchive_order', 'order', (int) $order['id']);

        flash('success', $archived ? 'Order archived — use "Show archived" to see it again.' : 'Order restored to your list.');
        redirect('/orders');
    }

    /**
     * Grab/Foodpanda-style rating: the customer reviews their own
     * COMPLETED order (1–5 stars + optional comment). One per order;
     * recomputes the restaurant's displayed rating.
     */
    public function review(string $id): void
    {
        $order = $this->ownOrder((int) $id);

        $rating = (int) ($_POST['rating'] ?? 0);
        $comment = trim((string) ($_POST['comment'] ?? ''));

        if (ReviewService::submit((int) $order['id'], $this->customerId(), $rating, $comment)) {
            flash('success', 'Thanks for your review! It updates the restaurant rating.');
        }

        redirect('/orders/' . (int) $order['id']);
    }

    /**
     * "Order again" (Grab/Foodpanda-style): re-adds the items of this
     * past order to the cart through CartService (all rules re-checked).
     */
    public function reorder(string $id): void
    {
        $order = $this->ownOrder((int) $id);

        if (!in_array($order['status'], ['completed', 'cancelled'], true)) {
            flash('error', 'You can only re-order a completed or cancelled order.');
            redirect('/orders/' . (int) $order['id']);
        }

        if (CartService::addFromOrder($this->customerId(), (int) $order['id']) === 0) {
            redirect('/orders/' . (int) $order['id']);
        }

        redirect('/cart');
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
            'payment'     => Payment::findByOrder((int) $order['id']),
            'fulfilment'  => Fulfilment::findByOrder((int) $order['id']),
            'review'      => Review::findByOrder((int) $order['id']),
            'canCancel'   => in_array($order['status'], ['pending_payment', 'confirmed'], true),
        ]);
    }

    /**
     * Simulated payment (Module 6): the customer pays their own
     * pending_payment order. Success confirms the order and creates
     * the fulfilment record; failure never marks it paid.
     */
    public function pay(string $id): void
    {
        $order = $this->ownOrder((int) $id);

        $method = (string) ($_POST['method'] ?? '');
        $simulateDecline = (string) ($_POST['simulate_decline'] ?? '0') === '1';

        if (PaymentService::pay((int) $order['id'], $this->customerId(), $method, $simulateDecline)) {
            flash('success', 'Payment successful! Your order is now confirmed.');
        }

        redirect('/orders/' . (int) $order['id']);
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
