<?php

namespace App\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\FoodItem;
use App\Models\Restaurant;
use App\Models\Review;
use App\Models\UserFavourite;
use App\Services\AuthService;

/**
 * Public restaurant browsing (Module 2 & 3). Only APPROVED restaurants
 * are visible here — pending and deactivated tenants stay private.
 */
class RestaurantController
{
    /** Customer-only: toggle this restaurant in the favourites list. */
    public function toggleFavourite(string $id): void
    {
        $user = AuthService::user();
        if ($user === null || !in_array('customer', $user['roles'], true)) {
            redirect('/login');
        }

        $restaurant = Restaurant::find((int) $id);
        if ($restaurant === null || $restaurant['status'] !== 'approved') {
            abort(404);
        }

        $saved = UserFavourite::toggle((int) $user['id'], (int) $restaurant['id']);
        flash('success', $saved
            ? $restaurant['name'] . ' added to your favourites.'
            : $restaurant['name'] . ' removed from your favourites.');

        redirect('/restaurants/' . (int) $restaurant['id']);
    }
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

        // Menu (Module 3): search text and category filter, both
        // whitelisted against this restaurant's own categories.
        $q = trim((string) ($_GET['q'] ?? ''));
        $categories = Category::allForRestaurant((int) $restaurant['id']);
        $categoryId = (int) ($_GET['category'] ?? 0);
        $validIds = array_map(fn (array $c): int => (int) $c['id'], $categories);
        if ($categoryId > 0 && !in_array($categoryId, $validIds, true)) {
            $categoryId = 0;
        }

        $viewer = AuthService::user();
        $isFavourite = $viewer !== null && in_array('customer', $viewer['roles'], true)
            && UserFavourite::exists((int) $viewer['id'], (int) $restaurant['id']);

        view('restaurants/show', [
            'title'       => $restaurant['name'],
            'restaurant'  => $restaurant,
            'branches'    => Branch::allForRestaurant((int) $restaurant['id']),
            'categories'  => $categories,
            'menuItems'   => FoodItem::menuForRestaurant(
                (int) $restaurant['id'],
                $q !== '' ? $q : null,
                $categoryId > 0 ? $categoryId : null
            ),
            'q'           => $q,
            'fCategory'   => $categoryId,
            'reviews'     => Review::listForRestaurant((int) $restaurant['id']),
            'reviewCount' => Review::countForRestaurant((int) $restaurant['id']),
            'isFavourite' => $isFavourite,
        ]);
    }
}
