<?php

namespace App\Controllers;

/**
 * Public landing page. The restaurant listing is added in Module 2.
 */
class HomeController
{
    public function index(): void
    {
        view('home', [
            'title' => 'Order from your favourite restaurants',
        ]);
    }
}
