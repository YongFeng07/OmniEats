<?php

namespace App\Services;

use App\Models\Category;

/**
 * Category business logic (Module 3). Ownership is always verified
 * against the tenant from the session.
 */
class CategoryService
{
    public static function create(int $restaurantId, array $data): bool
    {
        // Business rule: category names are unique per restaurant.
        if (Category::findByName($restaurantId, $data['name']) !== null) {
            flash('error', 'A category with this name already exists.');
            return false;
        }

        $id = Category::create($data, $restaurantId);

        activity('create_category', 'category', $id);

        return true;
    }

    public static function update(int $restaurantId, int $categoryId, array $data): bool
    {
        $category = self::ownedCategory($restaurantId, $categoryId);
        if ($category === null) {
            return false;
        }

        $duplicate = Category::findByName($restaurantId, $data['name']);
        if ($duplicate !== null && (int) $duplicate['id'] !== $categoryId) {
            flash('error', 'A category with this name already exists.');
            return false;
        }

        Category::update($categoryId, $data);

        activity('update_category', 'category', $categoryId);

        return true;
    }

    public static function delete(int $restaurantId, int $categoryId): bool
    {
        $category = self::ownedCategory($restaurantId, $categoryId);
        if ($category === null) {
            return false;
        }

        // Business rule: a category holding food items cannot be deleted
        // until its items are moved or deleted.
        if (Category::countItems($categoryId) > 0) {
            flash('error', 'This category still contains food items. Move or delete them first.');
            return false;
        }

        Category::delete($categoryId);

        activity('delete_category', 'category', $categoryId);

        return true;
    }

    /** Ownership check: category must exist AND belong to the tenant. */
    private static function ownedCategory(int $restaurantId, int $categoryId): ?array
    {
        $category = Category::find($categoryId);

        if ($category === null || (int) $category['restaurant_id'] !== $restaurantId) {
            flash('error', 'Category not found.');
            return null;
        }

        return $category;
    }
}
