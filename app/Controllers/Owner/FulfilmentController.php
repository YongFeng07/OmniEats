<?php

namespace App\Controllers\Owner;

use App\Models\Fulfilment;
use App\Models\Order;
use App\Services\AuthService;
use App\Services\FulfilmentService;

/**
 * Restaurant fulfilment management (Module 6): delivery / self-pickup
 * execution with validated status transitions.
 */
class FulfilmentController
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

        $status = in_array(
            $_GET['status'] ?? '',
            ['pending', 'out_for_delivery', 'ready_for_collection', 'completed', 'cancelled'],
            true
        ) ? $_GET['status'] : '';

        view('owner/fulfilments/index', [
            'title'        => 'Fulfilments',
            'fulfilments'  => Fulfilment::listForRestaurant($restaurantId, $status !== '' ? $status : null),
            'fStatus'      => $status,
        ]);
    }

    /** Advance a fulfilment (out for delivery / ready / completed). */
    public function transition(string $id): void
    {
        $restaurantId = $this->tenantId();
        if ($restaurantId === null) {
            redirect('/owner');
        }

        $status = (string) ($_POST['status'] ?? '');

        if (!FulfilmentService::updateStatus((int) $id, $restaurantId, $status)) {
            redirect('/owner/fulfilments');
        }

        flash('success', 'Fulfilment status updated to ' . str_replace('_', ' ', $status) . '.');
        redirect('/owner/fulfilments');
    }

    /** Fulfilment detail is shown on the order page (tenant check). */
    private function ownFulfilment(int $id): array
    {
        $fulfilment = Fulfilment::find($id);
        if ($fulfilment === null) {
            abort(404);
        }

        $order = Order::find((int) $fulfilment['order_id']);
        if ($order === null || (int) $order['restaurant_id'] !== $this->tenantId()) {
            abort(404);
        }

        return $fulfilment;
    }
}
