<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\FoodItem;
use App\Models\Plan;
use App\Models\Subscription;

/**
 * Subscription business logic (Module 4) including the unique feature:
 * ★ AUTOMATIC SUBSCRIPTION EXPIRY & QUOTA ENFORCEMENT.
 *
 * 1. syncExpiry() marks overdue subscriptions as expired whenever the
 *    platform processes a relevant request (no cron needed).
 * 2. enforceForBranch() / enforceForFoodItem() are called by the
 *    branch and food item services BEFORE creating records, so plan
 *    limits are checked server-side on every attempt.
 * 3. Upgrade/expiry messages are flashed for display.
 *
 * Payments are SIMULATED (academic project) — no real gateway, no card
 * data. The paid price is recorded as a snapshot for history.
 */
class SubscriptionService
{
    /**
     * Workflow 1 (blueprint): a newly registered restaurant receives an
     * initial Free-plan subscription automatically, so the owner can set
     * up branches/menu immediately while the restaurant is pending
     * approval. Returns false only when no active Free plan exists.
     */
    public static function bootstrapFree(int $restaurantId): bool
    {
        $plan = Plan::findByName('Free');
        if ($plan === null || (int) $plan['is_active'] !== 1) {
            return false;
        }

        Subscription::create($restaurantId, $plan);
        activity('subscription_bootstrap', 'subscription', $restaurantId);
        return true;
    }

    /** Auto-expire overdue subscriptions; returns how many changed. */
    public static function syncExpiry(): int
    {
        $ids = Subscription::expireOverdue();

        foreach ($ids as $id) {
            activity('subscription_expired', 'subscription', $id);
        }

        return count($ids);
    }

    /** The restaurant's active subscription (after auto-expiry), or null. */
    public static function currentForRestaurant(int $restaurantId): ?array
    {
        self::syncExpiry();
        return Subscription::activeForRestaurant($restaurantId);
    }

    /** Subscription + plan + usage snapshot for dashboards and checks. */
    public static function quota(int $restaurantId): array
    {
        $subscription = self::currentForRestaurant($restaurantId);

        $quota = [
            'subscription' => $subscription,
            'plan'         => null,
            'branches'     => Branch::countForRestaurant($restaurantId),
            'food_items'   => FoodItem::countForRestaurant($restaurantId),
        ];

        if ($subscription !== null) {
            $plan = Plan::find((int) $subscription['plan_id']);
            $quota['plan'] = $plan;
            $quota['max_branches'] = $plan !== null ? (int) $plan['max_branches'] : 0;
            $quota['max_food_items'] = ($plan !== null && $plan['max_food_items'] !== null)
                ? (int) $plan['max_food_items']
                : null; // null = unlimited
        }

        return $quota;
    }

    /** Quota check before creating a branch (called by BranchService). */
    public static function enforceForBranch(int $restaurantId): bool
    {
        $quota = self::quota($restaurantId);

        if ($quota['subscription'] === null) {
            flash('error', 'You need an active subscription to manage branches. Please choose a plan first.');
            return false;
        }

        if ($quota['branches'] >= (int) $quota['plan']['max_branches']) {
            flash('error', 'Your ' . $quota['plan']['name'] . ' plan allows up to '
                . (int) $quota['plan']['max_branches'] . ' branch(es). Upgrade your plan to add more.');
            return false;
        }

        return true;
    }

    /** Quota check before creating a food item (called by FoodItemService). */
    public static function enforceForFoodItem(int $restaurantId): bool
    {
        $quota = self::quota($restaurantId);

        if ($quota['subscription'] === null) {
            flash('error', 'You need an active subscription to manage your menu. Please choose a plan first.');
            return false;
        }

        $max = $quota['max_food_items'];
        if ($max !== null && $quota['food_items'] >= $max) {
            flash('error', 'Your ' . $quota['plan']['name'] . ' plan allows up to '
                . $max . ' food item(s). Upgrade your plan to add more.');
            return false;
        }

        return true;
    }

    /**
     * Choose / switch / renew a plan (simulated payment for paid plans).
     * Renewing the SAME plan extends its end date; switching plans (or
     * the first subscription) closes the current row and opens a new
     * one — the history keeps every previous record.
     */
    public static function subscribe(int $restaurantId, int $planId): bool
    {
        self::syncExpiry();

        $plan = Plan::find($planId);
        if ($plan === null || (int) $plan['is_active'] !== 1) {
            flash('error', 'This plan is not available.');
            return false;
        }

        $current = Subscription::activeForRestaurant($restaurantId);

        // Same plan → renew (extend), keeping the existing record.
        if ($current !== null && (int) $current['plan_id'] === $planId) {
            Subscription::extend((int) $current['id'], 1);
            activity('subscription_renew', 'subscription', (int) $current['id'], $plan['name']);
            return true;
        }

        // Different plan (or first subscription): close the current one
        // and open a new row with plan name/price snapshots.
        if ($current !== null) {
            Subscription::cancel((int) $current['id']);
            activity('subscription_cancelled', 'subscription', (int) $current['id'], 'Plan changed');
        }

        $id = Subscription::create($restaurantId, $plan);
        activity('subscription_create', 'subscription', $id, $plan['name']);

        if ((float) $plan['price'] > 0) {
            activity(
                'subscription_payment',
                'subscription',
                $id,
                'Simulated payment RM ' . number_format((float) $plan['price'], 2) . ' for ' . $plan['name']
            );
        }

        return true;
    }

    /** Cancel the restaurant's active subscription (soft delete). */
    public static function cancel(int $restaurantId): bool
    {
        self::syncExpiry();

        $subscription = Subscription::activeForRestaurant($restaurantId);
        if ($subscription === null) {
            flash('error', 'You do not have an active subscription.');
            return false;
        }

        Subscription::cancel((int) $subscription['id']);
        activity('subscription_cancelled', 'subscription', (int) $subscription['id'], 'Cancelled by restaurant');

        return true;
    }
}
