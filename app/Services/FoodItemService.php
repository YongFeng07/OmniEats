<?php

namespace App\Services;

use App\Models\Category;
use App\Models\FoodItem;

/**
 * Food item business logic (Module 3). The category is validated as
 * belonging to the tenant (not just existing), and images go through
 * UploadService's whitelist + MIME checks.
 */
class FoodItemService
{
    public static function create(int $restaurantId, array $data, array $files): bool
    {
        // M4 unique feature: plan quota enforcement before creating
        // tenant records (subscription must be active; limit respected).
        if (!SubscriptionService::enforceForFoodItem($restaurantId)) {
            return false;
        }

        if (!self::validCategory($restaurantId, (int) ($data['category_id'] ?? 0))) {
            return false;
        }

        $image = UploadService::store($files['image'] ?? [], 'foods');
        if (!$image['ok']) {
            return false;
        }

        $id = FoodItem::create($data, $restaurantId, $image['path']);

        activity('create_food_item', 'food_item', $id);

        return true;
    }

    public static function update(int $restaurantId, int $foodId, array $data, array $files): bool
    {
        $food = self::ownedFood($restaurantId, $foodId);
        if ($food === null) {
            return false;
        }

        if (!self::validCategory($restaurantId, (int) ($data['category_id'] ?? 0))) {
            return false;
        }

        $image = UploadService::store($files['image'] ?? [], 'foods');
        if (!$image['ok']) {
            return false;
        }

        // No new file submitted? Keep the current image.
        FoodItem::update($foodId, $data, $image['path'] ?? $food['image']);

        activity('update_food_item', 'food_item', $foodId);

        return true;
    }

    public static function delete(int $restaurantId, int $foodId): bool
    {
        $food = self::ownedFood($restaurantId, $foodId);
        if ($food === null) {
            return false;
        }

        // Business rule: an item referenced by past orders is part of
        // financial/order history and cannot be deleted — mark it
        // unavailable instead (order snapshots keep the old name/price).
        if (FoodItem::referencedByOrders($foodId)) {
            flash('error', 'This item appears in past orders and cannot be deleted. Mark it unavailable instead.');
            return false;
        }

        FoodItem::delete($foodId);

        activity('delete_food_item', 'food_item', $foodId);

        return true;
    }

    /** Availability toggle (re-checked again at checkout in Module 5). */
    public static function toggleAvailable(int $restaurantId, int $foodId): bool
    {
        $food = self::ownedFood($restaurantId, $foodId);
        if ($food === null) {
            return false;
        }

        $available = (int) $food['is_available'] !== 1;
        FoodItem::setAvailable($foodId, $available);

        activity($available ? 'food_item_available' : 'food_item_unavailable', 'food_item', $foodId);

        return true;
    }

    /** Ownership check: item must exist AND belong to the tenant. */
    private static function ownedFood(int $restaurantId, int $foodId): ?array
    {
        $food = FoodItem::find($foodId);

        if ($food === null || (int) $food['restaurant_id'] !== $restaurantId) {
            flash('error', 'Food item not found.');
            return null;
        }

        return $food;
    }

    /**
     * The chosen category must exist AND belong to the same restaurant.
     * A cross-tenant category id is reported as invalid, not leaked.
     */
    private static function validCategory(int $restaurantId, int $categoryId): bool
    {
        $category = Category::find($categoryId);

        if ($category === null || (int) $category['restaurant_id'] !== $restaurantId) {
            flash('error', 'Please choose a valid category.');
            return false;
        }

        return true;
    }
}
