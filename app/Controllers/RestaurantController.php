<?php

namespace App\Controllers;

use App\Models\Branch;
use App\Models\Restaurant;

/**
 * Public restaurant browsing (Module 2). Only APPROVED restaurants are
 * visible here — pending and deactivated tenants stay private.
 */
class RestaurantController
{
    public function index(): void
    {
        $q    = trim((string) ($_GET['q'] ?? ''));
        $open = in_array($_GET['open'] ?? '', ['open', 'closed'], true) ? $_GET['open'] : '';

        view('restaurants/index', [
            'title'       => 'Restaurants',
            'restaurants' => Restaurant::publicListing($q !== '' ? $q : null, $open !== '' ? $open : null),
            'q'           => $q,
            'fOpen'       => $open,
        ]);
    }

    public function show(string $id): void
    {
        $restaurant = Restaurant::find((int) $id);

        // Hidden from the public unless approved (tenant privacy).
        if ($restaurant === null || $restaurant['status'] !== 'approved') {
            abort(404);
        }

        view('restaurants/show', [
            'title'      => $restaurant['name'],
            'restaurant' => $restaurant,
            'branches'   => Branch::allForRestaurant((int) $restaurant['id']),
        ]);
    }
}
