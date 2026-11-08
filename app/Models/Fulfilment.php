<?php

namespace App\Models;

/**
 * Data access for fulfilments (Module 6): delivery / self-pickup
 * execution records, one per order (UNIQUE order_id).
 */
class Fulfilment extends Model
{
    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM fulfilments WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByOrder(int $orderId): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM fulfilments WHERE order_id = :oid');
        $stmt->execute(['oid' => $orderId]);
        return $stmt->fetch() ?: null;
    }

    /** Created automatically when a payment succeeds. */
    public static function create(int $orderId, string $type, ?string $address, ?string $phone): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO fulfilments (order_id, type, address, phone, status)
             VALUES (:oid, :type, :address, :phone, :status)'
        );
        $stmt->execute([
            'oid'     => $orderId,
            'type'    => $type,
            'address' => $address,
            'phone'   => $phone,
            'status'  => 'pending',
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function setStatus(int $id, string $status): void
    {
        $stmt = self::db()->prepare(
            "UPDATE fulfilments
                SET status = :status, completed_at = " . ($status === 'completed' ? 'NOW()' : 'NULL') . "
              WHERE id = :id"
        );
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    /**
     * Physical delete — allowed ONLY for eligible pending/cancelled
     * records (never completed financial-execution records; those stay
     * as the audit trail). Eligibility is enforced in FulfilmentService.
     */
    public static function delete(int $id): void
    {
        $stmt = self::db()->prepare('DELETE FROM fulfilments WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /** A restaurant's fulfilment records (tenant), optional filter. */
    public static function listForRestaurant(int $restaurantId, ?string $status = null): array
    {
        $sql = 'SELECT f.*, o.order_no, o.fulfilment_type AS order_type, u.name AS customer_name
                  FROM fulfilments f
                  JOIN orders o ON o.id = f.order_id
                  JOIN users u ON u.id = o.customer_id
                 WHERE o.restaurant_id = :rid';
        $params = ['rid' => $restaurantId];

        if (in_array($status, ['pending', 'out_for_delivery', 'ready_for_collection', 'completed', 'cancelled'], true)) {
            $sql .= ' AND f.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY f.id DESC';

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
