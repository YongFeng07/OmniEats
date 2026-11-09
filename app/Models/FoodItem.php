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

    /** Is this item referenced by any past order (history protection)? */
    public static function referencedByOrders(int $id): bool
    {
        $stmt = self::db()->prepare(
            'SELECT COUNT(*) FROM order_items WHERE food_item_id = :id'
        );
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Most-ordered items of a restaurant (real order history — the
     * "Most ordered" section, Grab-style social proof).
     */
    public static function popularForRestaurant(int $restaurantId, int $limit = 4): array
    {
        $stmt = self::db()->prepare(
            'SELECT f.*,
                    COALESCE(SUM(oi.quantity), 0) AS times_ordered
               FROM food_items f
          LEFT JOIN order_items oi ON oi.food_item_id = f.id
              WHERE f.restaurant_id = :rid
                AND f.is_available = 1
              GROUP BY f.id
              ORDER BY times_ordered DESC, f.id ASC
              LIMIT ' . max(1, min(8, $limit))
        );
        $stmt->execute(['rid' => $restaurantId]);
        return $stmt->fetchAll();
    }

    /** How many portions of this item have been ordered (social proof). */
    public static function timesOrdered(int $foodId): int
    {
        $stmt = self::db()->prepare(
            'SELECT COALESCE(SUM(quantity), 0) FROM order_items WHERE food_item_id = :id'
        );
        $stmt->execute(['id' => $foodId]);
        return (int) $stmt->fetchColumn();
    }
}
