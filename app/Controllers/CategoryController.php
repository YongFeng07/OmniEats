<?php

namespace App\Controllers;

use App\Models\Category;
use App\Models\FoodItem;

/**
 * Public category browse (Grab/Foodpanda-style): the category grid is
 * REAL database data (distinct categories across approved restaurants);
 * picking one shows every restaurant offering it with their items.
 */
class CategoryController
{
    /** All platform categories (grid page). */
    public function index(): void
    {
        view('categories/index', [
            'title'      => 'Browse Categories',
            'categories' => Category::distinctAcrossPlatform(),
        ]);
    }

    /** One category: restaurants + their matching items. */
    public function show(string $name): void
    {
        $restaurants = Category::restaurantsForCategoryName($name);

        if ($restaurants === []) {
            abort(404);
        }

        // Items per restaurant for this category.
        $menus = [];
        foreach ($restaurants as $restaurant) {
            $menus[(int) $restaurant['id']] = array_values(array_filter(
                FoodItem::menuForRestaurant((int) $restaurant['id'], null, (int) $restaurant['category_id']),
                fn (array $line): bool => (int) $line['is_available'] === 1
            ));
        }

        view('categories/show', [
            'title'       => $name . ' · Categories',
            'name'        => $name,
            'restaurants' => $restaurants,
            'menus'       => $menus,
        ]);
    }
}
