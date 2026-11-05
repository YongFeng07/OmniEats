<?php

namespace App\Controllers\Admin;

use App\Models\Subscription;
use App\Services\SubscriptionService;

/**
 * Platform subscription monitoring (Module 4) — super admin only.
 * Opening this page also runs the automatic expiry sweep.
 */
class SubscriptionController
{
    public function index(): void
    {
        // Monitoring a relevant request — the expiry sweep runs here too.
        SubscriptionService::syncExpiry();

        $status = in_array($_GET['status'] ?? '', ['active', 'expired', 'cancelled'], true)
            ? $_GET['status'] : '';

        view('admin/subscriptions/index', [
            'title'         => 'Subscription Monitoring',
            'subscriptions' => Subscription::adminListing($status !== '' ? $status : null),
            'fStatus'       => $status,
        ]);
    }
}
