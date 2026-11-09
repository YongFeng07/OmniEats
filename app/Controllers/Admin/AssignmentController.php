<?php

namespace App\Controllers\Admin;

use App\Models\Fulfilment;
use App\Models\RiderAssignmentHistory;
use App\Models\RiderProfile;
use App\Services\AuthService;
use App\Services\RiderService;

/**
 * ★ UNIQUE FEATURE — ADMIN MANUAL RIDER ASSIGNMENT (Student 3).
 * The admin picks an eligible rider for each delivery waiting for
 * assignment; reassignment works the same way and preserves the
 * previous attempt in rider_assignment_history.
 */
class AssignmentController
{
    /** Awaiting deliveries + eligible riders + assignment history. */
    public function index(): void
    {
        $awaiting = Fulfilment::awaitingAssignment();

        // Per-fulfilment assignment trails (for the history panel).
        $trails = [];
        foreach ($awaiting as $fulfilment) {
            $trails[(int) $fulfilment['id']] = RiderAssignmentHistory::forFulfilment((int) $fulfilment['id']);
        }

        view('admin/assignments/index', [
            'title'    => 'Rider Assignment',
            'awaiting' => $awaiting,
            'trails'   => $trails,
            'riders'   => RiderProfile::availableForAssignment(),
        ]);
    }

    /** Assign (or reassign) a rider to a delivery — admin only. */
    public function assign(string $id): void
    {
        $riderId = (int) ($_POST['rider_id'] ?? 0);
        $adminId = (int) AuthService::user()['id'];

        if (RiderService::assign((int) $id, $riderId, $adminId)) {
            flash('success', 'Rider assigned — the delivery now appears in the rider\'s portal.');
        }

        redirect('/admin/assignments');
    }
}
