<?php

namespace App\Models;

/**
 * Data access for carts (Module 5). One active cart per customer
 * (UNIQUE customer_id); a cart holds ONE restaurant's items only.
 */
class Cart extends Model
{
    public static function findForCustomer(int $customerId): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM carts WHERE customer_id = :cid');
        $stmt->execute(['cid' => $customerId]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $customerId, int $restaurantId): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO carts (customer_id, restaurant_id) VALUES (:cid, :rid)'
        );
        $stmt->execute(['cid' => $customerId, 'rid' => $restaurantId]);
        return (int) self::db()->lastInsertId();
    }

    /** Switch an empty cart to another restaurant (single-restaurant rule). */
    public static function setRestaurant(int $cartId, int $restaurantId): void
    {
        $stmt = self::db()->prepare('UPDATE carts SET restaurant_id = :rid WHERE id = :id');
        $stmt->execute(['rid' => $restaurantId, 'id' => $cartId]);
    }

    /** Delete the cart; cart_items cascade. */
    public static function delete(int $cartId): void
    {
        $stmt = self::db()->prepare('DELETE FROM carts WHERE id = :id');
        $stmt->execute(['id' => $cartId]);
    }
}
