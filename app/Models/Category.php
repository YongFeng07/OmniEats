<?php

namespace App\Models;

/**
 * Data access for the categories table (Module 3).
 */
class Category extends Model
{
    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM categories WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** All categories of one restaurant, in menu order. */
    public static function allForRestaurant(int $restaurantId): array
    {
        $stmt = self::db()->prepare(
            'SELECT * FROM categories WHERE restaurant_id = :rid ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute(['rid' => $restaurantId]);
        return $stmt->fetchAll();
    }

    /** Duplicate check: category names are unique per restaurant. */
    public static function findByName(int $restaurantId, string $name): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT * FROM categories WHERE restaurant_id = :rid AND name = :name'
        );
        $stmt->execute(['rid' => $restaurantId, 'name' => $name]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Platform-level category browse (Grab/Foodpanda-style): distinct
     * category names across APPROVED restaurants, with live item and
     * restaurant counts. The home-page category chips are driven by
     * this query — no hardcoded category lists.
     */
    public static function distinctAcrossPlatform(): array
    {
        $stmt = self::db()->prepare(
            'SELECT c.name,
                    COUNT(DISTINCT fi.id) AS item_count,
                    COUNT(DISTINCT r.id) AS restaurant_count
               FROM categories c
               JOIN restaurants r ON r.id = c.restaurant_id AND r.status = :status
          LEFT JOIN food_items fi ON fi.category_id = c.id
              WHERE fi.id IS NULL OR fi.is_available = 1
              GROUP BY c.name
              ORDER BY item_count DESC, c.name ASC
              LIMIT 16'
        );
        $stmt->execute(['status' => 'approved']);
        return $stmt->fetchAll();
    }

    /**
     * The restaurants that offer a category with this name (approved,
     * with their matching category id for item lookups).
     */
    public static function restaurantsForCategoryName(string $name): array
    {
        $stmt = self::db()->prepare(
            'SELECT r.*, c.id AS category_id
               FROM categories c
               JOIN restaurants r ON r.id = c.restaurant_id
              WHERE c.name = :name
                AND r.status = :status
              ORDER BY r.rating DESC, r.name ASC'
        );
        $stmt->execute(['name' => $name, 'status' => 'approved']);
        return $stmt->fetchAll();
    }

    /** How many food items live in a category (delete eligibility). */
    public static function countItems(int $categoryId): int
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) FROM food_items WHERE category_id = :cid');
        $stmt->execute(['cid' => $categoryId]);
        return (int) $stmt->fetchColumn();
    }

    public static function create(array $data, int $restaurantId): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO categories (restaurant_id, name, description, sort_order)
             VALUES (:rid, :name, :description, :sort)'
        );
        $stmt->execute([
            'rid'         => $restaurantId,
            'name'        => $data['name'],
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'sort'        => (int) ($data['sort_order'] ?? 0),
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = self::db()->prepare(
            'UPDATE categories
                SET name = :name, description = :description, sort_order = :sort
              WHERE id = :id'
        );
        $stmt->execute([
            'name'        => $data['name'],
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'sort'        => (int) ($data['sort_order'] ?? 0),
            'id'          => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = self::db()->prepare('DELETE FROM categories WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
