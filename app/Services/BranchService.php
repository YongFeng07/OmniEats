<?php

namespace App\Services;

use App\Models\Branch;

/**
 * Branch business logic (Module 2). Ownership is always verified
 * against the tenant from the session — a branch id belonging to
 * another restaurant is reported as "not found", never leaked.
 */
class BranchService
{
    public static function create(int $restaurantId, array $data): bool
    {
        // M4 unique feature: plan quota enforcement before creating
        // tenant records (subscription must be active; limit respected).
        if (!SubscriptionService::enforceForBranch($restaurantId)) {
            return false;
        }

        $id = Branch::create($data, $restaurantId);

        activity('create_branch', 'restaurant_branch', $id);

        return true;
    }

    public static function update(int $restaurantId, int $branchId, array $data): bool
    {
        $branch = self::ownedBranch($restaurantId, $branchId);
        if ($branch === null) {
            return false;
        }

        Branch::update($branchId, $data);

        activity('update_branch', 'restaurant_branch', $branchId);

        return true;
    }

    public static function delete(int $restaurantId, int $branchId): bool
    {
        $branch = self::ownedBranch($restaurantId, $branchId);
        if ($branch === null) {
            return false;
        }

        // Business rule: a restaurant must always have at least one branch.
        if (Branch::countForRestaurant($restaurantId) <= 1) {
            flash('error', 'You cannot delete your only branch.');
            return false;
        }

        Branch::delete($branchId);

        activity('delete_branch', 'restaurant_branch', $branchId);

        return true;
    }

    /** Ownership check: branch must exist AND belong to the tenant. */
    private static function ownedBranch(int $restaurantId, int $branchId): ?array
    {
        $branch = Branch::find($branchId);

        if ($branch === null || (int) $branch['restaurant_id'] !== $restaurantId) {
            flash('error', 'Branch not found.');
            return null;
        }

        return $branch;
    }
}
