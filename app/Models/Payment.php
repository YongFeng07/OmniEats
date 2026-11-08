<?php

namespace App\Models;

/**
 * Data access for payments (Module 6).
 *
 * AUDIT TRAIL: successful and voided payment records are NEVER
 * physically deleted — only pending/failed test records may be removed.
 * No card numbers or CVVs are ever stored; online payments carry only
 * a simulated gateway reference.
 */
class Payment extends Model
{
    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM payments WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** The single payment record of an order (UNIQUE order_id). */
    public static function findByOrder(int $orderId): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM payments WHERE order_id = :oid');
        $stmt->execute(['oid' => $orderId]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $orderId, string $method, float $amount): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO payments (order_id, method, amount, status)
             VALUES (:oid, :method, :amount, :status)'
        );
        $stmt->execute([
            'oid'    => $orderId,
            'method' => $method,
            'amount' => sprintf('%.2f', $amount),
            'status' => 'pending',
        ]);
        return (int) self::db()->lastInsertId();
    }

    /** Reset a failed/pending record for a retry with a chosen method. */
    public static function reset(int $id, string $method, float $amount): void
    {
        $stmt = self::db()->prepare(
            'UPDATE payments SET method = :method, amount = :amount, status = :status,
                    paid_at = NULL, transaction_ref = NULL
              WHERE id = :id'
        );
        $stmt->execute([
            'method' => $method,
            'amount' => sprintf('%.2f', $amount),
            'status' => 'pending',
            'id'     => $id,
        ]);
    }

    public static function markSuccess(int $id, string $transactionRef): void
    {
        $stmt = self::db()->prepare(
            "UPDATE payments
                SET status = 'success', transaction_ref = :ref, paid_at = NOW()
              WHERE id = :id"
        );
        $stmt->execute(['ref' => $transactionRef, 'id' => $id]);
    }

    public static function markFailed(int $id): void
    {
        $stmt = self::db()->prepare("UPDATE payments SET status = 'failed' WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    /** Void a completed payment (refund/audit) — never deleted. */
    public static function markVoid(int $id): void
    {
        $stmt = self::db()->prepare("UPDATE payments SET status = 'void' WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    /** Physical delete — allowed only for pending/failed test records. */
    public static function delete(int $id): void
    {
        $stmt = self::db()->prepare('DELETE FROM payments WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /** Sum of successful payments across the platform (admin stat). */
    public static function totalSuccessful(): float
    {
        $value = self::db()->query(
            "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'success'"
        )->fetchColumn();
        return (float) $value;
    }

    /** Today's successful payments for one restaurant (merchant stat). */
    public static function todayTotalForRestaurant(int $restaurantId): float
    {
        $stmt = self::db()->prepare(
            "SELECT COALESCE(SUM(p.amount), 0)
               FROM payments p
               JOIN orders o ON o.id = p.order_id
              WHERE o.restaurant_id = :rid
                AND p.status = 'success'
                AND DATE(p.paid_at) = CURDATE()"
        );
        $stmt->execute(['rid' => $restaurantId]);
        return (float) $stmt->fetchColumn();
    }

    /** This month's successful payments for one restaurant (merchant stat). */
    public static function monthTotalForRestaurant(int $restaurantId): float
    {
        $stmt = self::db()->prepare(
            "SELECT COALESCE(SUM(p.amount), 0)
               FROM payments p
               JOIN orders o ON o.id = p.order_id
              WHERE o.restaurant_id = :rid
                AND p.status = 'success'
                AND DATE_FORMAT(p.paid_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')"
        );
        $stmt->execute(['rid' => $restaurantId]);
        return (float) $stmt->fetchColumn();
    }

    /** A restaurant's payment records (tenant), optional status filter. */
    public static function listForRestaurant(int $restaurantId, ?string $status = null): array
    {
        $sql = 'SELECT p.*, o.order_no, u.name AS customer_name
                  FROM payments p
                  JOIN orders o ON o.id = p.order_id
                  JOIN users u ON u.id = o.customer_id
                 WHERE o.restaurant_id = :rid';
        $params = ['rid' => $restaurantId];

        if (in_array($status, ['pending', 'success', 'failed', 'void'], true)) {
            $sql .= ' AND p.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY p.id DESC';

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
