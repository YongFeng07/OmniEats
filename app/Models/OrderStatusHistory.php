<?php

namespace App\Models;

/**
 * Data access for order_status_history (Module 5 unique feature:
 * database-backed status timeline for live order tracking).
 */
class OrderStatusHistory extends Model
{
    public static function add(
        int $orderId,
        ?string $fromStatus,
        string $toStatus,
        ?int $changedBy,
        ?string $note = null
    ): void {
        $stmt = self::db()->prepare(
            'INSERT INTO order_status_history (order_id, from_status, to_status, changed_by, note)
             VALUES (:oid, :from, :to, :by, :note)'
        );
        $stmt->execute([
            'oid'  => $orderId,
            'from' => $fromStatus,
            'to'   => $toStatus,
            'by'   => $changedBy,
            'note' => $note,
        ]);
    }

    /** Full timeline for one order, oldest first, with actor names. */
    public static function allForOrder(int $orderId): array
    {
        $stmt = self::db()->prepare(
            'SELECT h.*, u.name AS actor_name
               FROM order_status_history h
          LEFT JOIN users u ON u.id = h.changed_by
              WHERE h.order_id = :oid
              ORDER BY h.id ASC'
        );
        $stmt->execute(['oid' => $orderId]);
        return $stmt->fetchAll();
    }
}
