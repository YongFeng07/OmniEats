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
