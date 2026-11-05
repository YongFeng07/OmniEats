<?php

namespace App\Controllers\Admin;

use App\Models\ActivityLog;

/**
 * Platform activity overview (admin monitoring) — the audit trail of
 * logins, registrations, CRUD actions, status changes and payments.
 */
class ActivityController
{
    public function index(): void
    {
        view('admin/activity/index', [
            'title'      => 'Activity Overview',
            'activities' => ActivityLog::latest(100),
        ]);
    }
}
