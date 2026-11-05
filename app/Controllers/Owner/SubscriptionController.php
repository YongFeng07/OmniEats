<?php

namespace App\Controllers\Owner;

use App\Models\Plan;
use App\Models\Subscription;
use App\Services\AuthService;
use App\Services\SubscriptionService;

/**
 * Restaurant subscription management (Module 4): plan picker, simulated
 * payment, renew/upgrade/downgrade, cancel, and quota usage display.
 * Tenant always comes from the session.
 */
class SubscriptionController
{
    private function tenantId(): ?int
    {
        $restaurantId = AuthService::user()['restaurant_id'] ?? null;
        return $restaurantId !== null ? (int) $restaurantId : null;
    }

    public function index(): void
    {
        $restaurantId = $this->tenantId();
        if ($restaurantId === null) {
            redirect('/owner');
        }

        view('owner/subscription/index', [
            'title'  => 'My Subscription',
            'quota'  => SubscriptionService::quota($restaurantId),
            'plans'  => Plan::allActive(),
            'history'=> Subscription::historyForRestaurant($restaurantId),
        ]);
    }

    /** Choose / switch / renew a plan (simulated payment). */
    public function subscribe(string $planId): void
    {
        $restaurantId = $this->tenantId();
        if ($restaurantId === null) {
            redirect('/owner');
        }

        if (!SubscriptionService::subscribe($restaurantId, (int) $planId)) {
            redirect('/owner/subscription');
        }

        flash('success', 'Subscription updated. This academic project simulates the payment — no real charges apply.');
        redirect('/owner/subscription');
    }

    /** Cancel the active subscription (soft delete, history preserved). */
    public function cancel(): void
    {
        $restaurantId = $this->tenantId();
        if ($restaurantId === null) {
            redirect('/owner');
        }

        if (!SubscriptionService::cancel($restaurantId)) {
            redirect('/owner/subscription');
        }

        flash('success', 'Subscription cancelled. You can choose a new plan at any time.');
        redirect('/owner/subscription');
    }
}
