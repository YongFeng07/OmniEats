<?php

namespace App\Controllers;

use App\Models\Category;
use App\Models\Restaurant;

/**
 * Public landing page: REAL database-driven category chips (distinct
 * categories across approved restaurants) and a featured-restaurant
 * teaser (approved + OPEN restaurants only — no hardcoded data).
 */
class HomeController
{
    public function index(): void
    {
        $featured = array_slice(Restaurant::publicListing(null, 'open'), 0, 3);

        view('home', [
            'title'      => 'Order from your favourite restaurants',
            'featured'   => $featured,
            'categories' => Category::distinctAcrossPlatform(),
        ]);
    }
}
