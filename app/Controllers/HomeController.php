<?php

namespace App\Controllers;

use App\Models\Restaurant;

/**
 * Public landing page with a featured-restaurant teaser fed from the
 * database (approved + OPEN restaurants only — no hardcoded data).
 */
class HomeController
{
    public function index(): void
    {
        $featured = array_slice(Restaurant::publicListing(null, 'open'), 0, 3);

        view('home', [
            'title'    => 'Order from your favourite restaurants',
            'featured' => $featured,
        ]);
    }
}
