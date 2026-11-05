<?php

namespace App\Services;

use App\Models\Plan;

/**
 * Subscription plan business logic (Module 4) — admin.
 * Plans are archived (is_active = 0), never hard-deleted, so that
 * subscription history keeps its references intact.
 */
class PlanService
{
    public static function create(array $data): bool
    {
        if (Plan::findByName($data['name']) !== null) {
            flash('error', 'A plan with this name already exists.');
            return false;
        }

        $id = Plan::create($data);

        activity('create_plan', 'subscription_plan', $id);

        return true;
    }

    public static function update(int $planId, array $data): bool
    {
        $plan = Plan::find($planId);
        if ($plan === null) {
            flash('error', 'Plan not found.');
            return false;
        }

        $duplicate = Plan::findByName($data['name']);
        if ($duplicate !== null && (int) $duplicate['id'] !== $planId) {
            flash('error', 'A plan with this name already exists.');
            return false;
        }

        Plan::update($planId, $data);

        activity('update_plan', 'subscription_plan', $planId);

        return true;
    }

    /** Archive or restore a plan (DELETE via soft archive). */
    public static function toggleActive(int $planId): bool
    {
        $plan = Plan::find($planId);
        if ($plan === null) {
            flash('error', 'Plan not found.');
            return false;
        }

        $activate = (int) $plan['is_active'] !== 1;

        // Business rule: a plan with active subscribers cannot be archived.
        if (!$activate && Plan::countActiveSubscriptions($planId) > 0) {
            flash('error', 'This plan has active subscribers and cannot be archived.');
            return false;
        }

        Plan::setActive($planId, $activate);

        activity($activate ? 'restore_plan' : 'archive_plan', 'subscription_plan', $planId);

        return true;
    }
}
