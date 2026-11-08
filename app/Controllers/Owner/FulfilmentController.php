<?php

namespace App\Controllers\Owner;

use App\Models\Fulfilment;
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

    /** Assign the restaurant's own rider to a delivery fulfilment. */
    public function driver(string $id): void
    {
        $restaurantId = $this->tenantId();
        if ($restaurantId === null) {
            redirect('/owner');
        }

        $data = [
            'driver_name'    => (string) ($_POST['driver_name'] ?? ''),
            'driver_vehicle' => (string) ($_POST['driver_vehicle'] ?? ''),
            'driver_phone'   => (string) ($_POST['driver_phone'] ?? ''),
        ];

        if (!FulfilmentService::assignDriver((int) $id, $restaurantId, $data)) {
            redirect('/owner/orders/' . (int) $this->orderIdFor((int) $id));
        }

        flash('success', 'Rider assigned — the customer now sees the rider card.');
        redirect('/owner/orders/' . (int) $this->orderIdFor((int) $id));
    }

    /** M6 DELETE — eligible pending/cancelled records only (owner). */
    public function delete(string $id): void
    {
        $restaurantId = $this->tenantId();
        if ($restaurantId === null) {
            redirect('/owner');
        }

        if (!FulfilmentService::delete((int) $id, $restaurantId)) {
            redirect('/owner/fulfilments');
        }

        flash('success', 'Fulfilment record deleted.');
        redirect('/owner/fulfilments');
    }

    /** The order a fulfilment belongs to (for redirects). */
    private function orderIdFor(int $fulfilmentId): int
    {
        $fulfilment = Fulfilment::find($fulfilmentId);
        return $fulfilment !== null ? (int) $fulfilment['order_id'] : 0;
    }
}
