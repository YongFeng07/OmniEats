<?php

namespace App\Models;

/**
 * Data access for cart_items (Module 5).
 */
class CartItem extends Model
{
    /** Cart lines joined with their food item data. */
    public static function allForCart(int $cartId): array
    {
        $stmt = self::db()->prepare(
            'SELECT ci.id, ci.food_item_id, ci.quantity, ci.note,
                    f.name AS food_name, f.price AS unit_price, f.image, f.is_available,
                    f.restaurant_id
               FROM cart_items ci
               JOIN food_items f ON f.id = ci.food_item_id
              WHERE ci.cart_id = :cid
              ORDER BY ci.id ASC'
        );
        $stmt->execute(['cid' => $cartId]);
        return $stmt->fetchAll();
    }

    /** Add a line, or increase the quantity when it already exists. */
    public static function addOrIncrement(int $cartId, int $foodItemId, int $quantity): void
    {
        $stmt = self::db()->prepare(
            'INSERT INTO cart_items (cart_id, food_item_id, quantity)
             VALUES (:cid, :fid, :qty)
             ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)'
        );
        $stmt->execute(['cid' => $cartId, 'fid' => $foodItemId, 'qty' => $quantity]);
    }

    /** Per-item note (e.g. "no chilli"), 120 characters max. */
    public static function setNote(int $cartId, int $foodItemId, string $note): void
    {
        $stmt = self::db()->prepare(
            'UPDATE cart_items SET note = :note WHERE cart_id = :cid AND food_item_id = :fid'
        );
        $stmt->execute(['note' => $note, 'cid' => $cartId, 'fid' => $foodItemId]);
    }

    public static function updateQuantity(int $cartId, int $foodItemId, int $quantity): void
    {
        $stmt = self::db()->prepare(
            'UPDATE cart_items SET quantity = :qty WHERE cart_id = :cid AND food_item_id = :fid'
        );
        $stmt->execute(['qty' => $quantity, 'cid' => $cartId, 'fid' => $foodItemId]);
    }

    public static function remove(int $cartId, int $foodItemId): void
    {
        $stmt = self::db()->prepare(
            'DELETE FROM cart_items WHERE cart_id = :cid AND food_item_id = :fid'
        );
        $stmt->execute(['cid' => $cartId, 'fid' => $foodItemId]);
    }

    /** Total number of items (sum of quantities) in a cart. */
    public static function countForCart(int $cartId): int
    {
        $stmt = self::db()->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE cart_id = :cid');
        $stmt->execute(['cid' => $cartId]);
        return (int) $stmt->fetchColumn();
    }
}
