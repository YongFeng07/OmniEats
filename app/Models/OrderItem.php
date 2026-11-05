<?php

namespace App\Models;

/**
 * Data access for order_items (Module 5). Rows store the food name and
 * unit price AT PURCHASE TIME — later menu edits never change history.
 */
class OrderItem extends Model
{
    public static function create(int $orderId, array $snapshot): void
    {
        $stmt = self::db()->prepare(
            'INSERT INTO order_items (order_id, food_item_id, food_name, unit_price, quantity, subtotal)
             VALUES (:oid, :fid, :name, :price, :qty, :subtotal)'
        );
        $stmt->execute([
            'oid'      => $orderId,
            'fid'      => (int) $snapshot['food_item_id'],
            'name'     => $snapshot['food_name'],
            'price'    => sprintf('%.2f', $snapshot['unit_price']),
            'qty'      => (int) $snapshot['quantity'],
            'subtotal' => sprintf('%.2f', $snapshot['subtotal']),
        ]);
    }

    public static function allForOrder(int $orderId): array
    {
        $stmt = self::db()->prepare('SELECT * FROM order_items WHERE order_id = :oid ORDER BY id');
        $stmt->execute(['oid' => $orderId]);
        return $stmt->fetchAll();
    }
}
