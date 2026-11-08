<?php

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AuthService;
use App\Services\SubscriptionService;

/**
 * Role-specific dashboard landing pages.
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
        view('dashboard', $this->data('Platform Administration') + [
            // Platform overview stats (admin monitoring).
            'stats' => [
                'users'               => User::count(),
                'restaurants'         => Restaurant::countByStatus('approved'),
                'restaurants_pending' => Restaurant::countByStatus('pending'),
                'active_subscriptions'=> Subscription::countActive(),
                'orders'              => Order::countAll(),
                'revenue'             => Payment::totalSuccessful(),
            ],
        ]);
    }

    public function owner(): void
    {
        $data = $this->data('Restaurant Dashboard');
        $tenantId = $data['tenant'] !== null ? (int) $data['tenant']['id'] : null;

        if ($tenantId === null) {
            view('dashboard', $data);
            return;
        }

        // Merchant dashboard: sales stats (plan "sales reporting") and
        // incoming orders.
        view('owner/dashboard', $data + [
            'todaySales'     => Payment::todayTotalForRestaurant($tenantId),
            'monthSales'     => Payment::monthTotalForRestaurant($tenantId),
            'todayOrders'    => Order::countTodayForRestaurant($tenantId),
            'incomingOrders' => Order::listForRestaurant($tenantId, 'confirmed'),
        ]);
    }

    public function customer(): void
    {
        view('dashboard', $this->data('My OmniEats'));
    }

    private function data(string $heading): array
    {
        $user = AuthService::user();
        $tenant = !empty($user['restaurant_id'])
            ? Restaurant::find((int) $user['restaurant_id'])
            : null;

        return [
            'title'            => $heading,
            'heading'          => $heading,
            'user'             => $user,
            'tenant'           => $tenant,
            // Subscription quota for the owner dashboard card (M4).
            'subscriptionInfo' => $tenant !== null
                ? SubscriptionService::quota((int) $tenant['id'])
                : ['subscription' => null, 'plan' => null],
        ];
    }
}
