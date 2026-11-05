<?php

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\Restaurant;
use App\Services\AuthService;

/**
 * Role-specific dashboard landing pages. The real module dashboards
 * (orders, menu, subscription...) replace these placeholders as each
 * module is implemented in Phase 5.
 */
class DashboardController
{
    /** Logged-in users are sent to their role's dashboard. */
    public function index(): void
    {
        redirect(AuthMiddleware::homeFor(AuthService::user()));
    }

    public function admin(): void
    {
        view('dashboard', $this->data('Platform Administration'));
    }

    public function owner(): void
    {
        view('dashboard', $this->data('Restaurant Dashboard'));
    }

    public function customer(): void
    {
        view('dashboard', $this->data('My OmniEats'));
    }

    private function data(string $heading): array
    {
        $user = AuthService::user();

        return [
            'title'   => $heading,
            'heading' => $heading,
            'user'    => $user,
            'tenant'  => !empty($user['restaurant_id'])
                ? Restaurant::find((int) $user['restaurant_id'])
                : null,
        ];
    }
}
