<?php

namespace App\Controllers\Owner;

use App\Models\Branch;
use App\Models\Fulfilment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\User;
use App\Services\AuthService;
use App\Services\OrderService;

/**
 * Restaurant order management (Module 5) — owner and staff, scoped to
 * the tenant from the session. Status transitions are validated by
 * OrderService against the transition map.
 */
class OrderController
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

        $status = in_array($_GET['status'] ?? '', Order::statuses(), true) ? $_GET['status'] : '';

        view('owner/orders/index', [
            'title'  => 'Orders',
            'orders' => Order::listForRestaurant($restaurantId, $status !== '' ? $status : null),
            'fStatus'=> $status,
        ]);
    }

    public function show(string $id): void
    {
        $order = $this->ownOrder((int) $id);

        $user = User::find((int) $order['customer_id']);

        view('owner/orders/show', [
            'title'      => 'Order ' . $order['order_no'],
            'order'      => $order,
            'customer'   => $user,
            'items'      => OrderItem::allForOrder((int) $order['id']),
            'history'    => OrderStatusHistory::allForOrder((int) $order['id']),
            'branch'     => !empty($order['branch_id']) ? Branch::find((int) $order['branch_id']) : null,
            'payment'    => Payment::findByOrder((int) $order['id']),
            'fulfilment' => Fulfilment::findByOrder((int) $order['id']),
            'transitions'=> OrderService::TRANSITIONS[$order['status']] ?? [],
        ]);
    }

    /** Advance the order along the validated status timeline. */
    public function transition(string $id): void
    {
        $order = $this->ownOrder((int) $id);

        $newStatus = (string) ($_POST['status'] ?? '');
        $note = trim((string) ($_POST['note'] ?? ''));

        if (!OrderService::transition((int) $order['id'], $newStatus, AuthService::user(), $note)) {
            redirect('/owner/orders/' . (int) $order['id']);
        }

        flash('success', 'Order status updated to ' . str_replace('_', ' ', $newStatus) . '.');
        redirect('/owner/orders/' . (int) $order['id']);
    }

    /** Tenant check: the order must belong to the session's restaurant. */
    private function ownOrder(int $id): array
    {
        $order = Order::find($id);

        if ($order === null || (int) $order['restaurant_id'] !== $this->tenantId()) {
            abort(404);
        }

        return $order;
    }
}
