<?php

namespace App\Models;

/**
 * Data access for the food_items table (Module 3). Every query that
 * reads tenant data filters by restaurant_id.
 */
class FoodItem extends Model
{
    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM food_items WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Menu for one restaurant, optionally filtered by search text and
     * category — used on the public restaurant page and owner list.
     */
    public static function menuForRestaurant(int $restaurantId, ?string $q = null, ?int $categoryId = null): array
    {
        $sql = 'SELECT f.*,
                       c.name AS category_name,
                       c.sort_order AS category_sort
                  FROM food_items f
                  JOIN categories c ON c.id = f.category_id
                 WHERE f.restaurant_id = :rid';
        $params = ['rid' => $restaurantId];

        if ($q !== null && $q !== '') {
            $sql .= ' AND f.name LIKE :q';
            $params['q'] = '%' . $q . '%';
        }

        if ($categoryId !== null) {
            $sql .= ' AND f.category_id = :cid';
            $params['cid'] = $categoryId;
        }

        $sql .= ' ORDER BY c.sort_order ASC, c.id ASC, f.name ASC';

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function countForRestaurant(int $restaurantId): int
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) FROM food_items WHERE restaurant_id = :rid');
        $stmt->execute(['rid' => $restaurantId]);
        return (int) $stmt->fetchColumn();
    }

    public static function create(array $data, int $restaurantId, ?string $image): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO food_items (restaurant_id, category_id, name, description, price, image, is_available)
             VALUES (:rid, :cid, :name, :description, :price, :image, :available)'
        );
        $stmt->execute([
            'rid'         => $restaurantId,
            'cid'         => (int) $data['category_id'],
            'name'        => $data['name'],
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'price'       => sprintf('%.2f', (float) $data['price']),
            'image'       => $image,
            'available'   => ($data['is_available'] ?? '1') === '1' ? 1 : 0,
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, array $data, ?string $image): void
    {
        $stmt = self::db()->prepare(
            'UPDATE food_items
                SET category_id = :cid, name = :name, description = :description,
                    price = :price, image = :image, is_available = :available
              WHERE id = :id'
        );
        $stmt->execute([
            'cid'         => (int) $data['category_id'],
            'name'        => $data['name'],
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'price'       => sprintf('%.2f', (float) $data['price']),
            'image'       => $image,
            'available'   => ($data['is_available'] ?? '1') === '1' ? 1 : 0,
            'id'          => $id,
        ]);
    }

    /** Availability control per item (re-checked at checkout in M5). */
    public static function setAvailable(int $id, bool $available): void
    {
        $stmt = self::db()->prepare('UPDATE food_items SET is_available = :av WHERE id = :id');
        $stmt->execute(['av' => $available ? 1 : 0, 'id' => $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = self::db()->prepare('DELETE FROM food_items WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
