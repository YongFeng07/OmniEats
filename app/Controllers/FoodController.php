<?php

namespace App\Controllers;

use App\Models\Category;
use App\Models\FoodItem;
use App\Models\Restaurant;

/**
 * Public food item detail (Module 3). Items of non-approved restaurants
 * are hidden from the public (tenant privacy).
 */
class FoodController
{
    public function show(string $id): void
    {
        $item = FoodItem::find((int) $id);
        if ($item === null) {
            abort(404);
        }

        $restaurant = Restaurant::find((int) $item['restaurant_id']);
        if ($restaurant === null || $restaurant['status'] !== 'approved') {
            abort(404);
        }

        view('foods/show', [
            'title'      => $item['name'],
            'item'       => $item,
            'restaurant' => $restaurant,
            'category'   => Category::find((int) $item['category_id']),
        ]);
    }
}
