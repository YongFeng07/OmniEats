<?php

namespace App\Controllers\Admin;

use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\RestaurantService;

/**
 * Platform restaurant management (Module 2): approval lifecycle and
 * monitoring — super admin only (route guard).
 */
class RestaurantController
{
    public function index(): void
    {
        $q      = trim((string) ($_GET['q'] ?? ''));
        $status = in_array($_GET['status'] ?? '', ['pending', 'approved', 'deactivated'], true)
            ? $_GET['status'] : '';

        view('admin/restaurants/index', [
            'title'       => 'Restaurant Management',
            'restaurants' => Restaurant::adminListing($q !== '' ? $q : null, $status !== '' ? $status : null),
            'q'           => $q,
            'fStatus'     => $status,
        ]);
    }

    public function show(string $id): void
    {
        $restaurant = Restaurant::find((int) $id);
        if ($restaurant === null) {
            abort(404);
        }

        view('admin/restaurants/show', [
            'title'      => 'Restaurant — ' . $restaurant['name'],
            'restaurant' => $restaurant,
            'owner'      => User::find((int) $restaurant['owner_id']),
            'branches'   => Branch::allForRestaurant((int) $restaurant['id']),
        ]);
    }

    /** Approve a pending (or reactivate a deactivated) restaurant. */
    public function approve(string $id): void
    {
        if (!RestaurantService::setApproval((int) $id, 'approved')) {
            redirect('/admin/restaurants/' . (int) $id);
        }

        flash('success', 'Restaurant approved — it is now visible to customers.');
        redirect('/admin/restaurants/' . (int) $id);
    }

    /** Deactivate an approved restaurant (archive) or reactivate it. */
    public function toggleStatus(string $id): void
    {
        $restaurant = Restaurant::find((int) $id);
        if ($restaurant === null) {
            abort(404);
        }

        $newStatus = $restaurant['status'] === 'deactivated' ? 'approved' : 'deactivated';

        if (!RestaurantService::setApproval((int) $id, $newStatus)) {
            redirect('/admin/restaurants/' . (int) $id);
        }

        flash(
            'success',
            $newStatus === 'deactivated'
                ? 'Restaurant deactivated — it is hidden from customers but its records are preserved.'
                : 'Restaurant reactivated.'
        );
        redirect('/admin/restaurants/' . (int) $id);
    }
}
