<?php

namespace App\Models;

/**
 * Data access for subscriptions (Module 4). Rows are NEVER deleted —
 * the history (plan name and price snapshots) is preserved; the end
 * states are cancelled/expired.
 */
class Subscription extends Model
{
    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM subscriptions WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** The currently active subscription of a restaurant, or null. */
    public static function activeForRestaurant(int $restaurantId): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT * FROM subscriptions
              WHERE restaurant_id = :rid AND status = :status
              ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute(['rid' => $restaurantId, 'status' => 'active']);
        return $stmt->fetch() ?: null;
    }

    /** Active subscriptions across the platform (admin dashboard stat). */
    public static function countActive(): int
    {
        return (int) self::db()->query(
            "SELECT COUNT(*) FROM subscriptions WHERE status = 'active'"
        )->fetchColumn();
    }

    /** Full history for one restaurant, newest first. */
    public static function historyForRestaurant(int $restaurantId): array
    {
        $stmt = self::db()->prepare(
            'SELECT * FROM subscriptions WHERE restaurant_id = :rid ORDER BY id DESC'
        );
        $stmt->execute(['rid' => $restaurantId]);
        return $stmt->fetchAll();
    }

    /** Create a new subscription row (plan snapshot stored). */
    public static function create(int $restaurantId, array $plan): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO subscriptions (restaurant_id, plan_id, plan_name, price_paid, starts_at, ends_at, status)
             VALUES (:rid, :pid, :pname, :price, NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH), :status)'
        );
        $stmt->execute([
            'rid'    => $restaurantId,
            'pid'    => (int) $plan['id'],
            'pname'  => $plan['name'],
            'price'  => sprintf('%.2f', (float) $plan['price']),
            'status' => 'active',
        ]);
        return (int) self::db()->lastInsertId();
    }

    /** Renew: extend the end date by N months (from now if overdue). */
    public static function extend(int $id, int $months): void
    {
        $stmt = self::db()->prepare(
            'UPDATE subscriptions
                SET ends_at = DATE_ADD(GREATEST(ends_at, NOW()), INTERVAL :m MONTH),
                    status = :status
              WHERE id = :id'
        );
        $stmt->execute(['m' => $months, 'status' => 'active', 'id' => $id]);
    }

    /** Cancel (soft): history preserved. */
    public static function cancel(int $id): void
    {
        $stmt = self::db()->prepare('UPDATE subscriptions SET status = :status WHERE id = :id');
        $stmt->execute(['status' => 'cancelled', 'id' => $id]);
    }

    /**
     * ★ UNIQUE FEATURE — automatic expiry: mark every overdue active
     * subscription as expired and return the affected ids so the
     * service can log them.
     */
    public static function expireOverdue(): array
    {
        $stmt = self::db()->query(
            "SELECT id FROM subscriptions WHERE status = 'active' AND ends_at < NOW()"
        );
        $ids = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $ids = array_map('intval', $ids);

        if ($ids !== []) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $update = self::db()->prepare(
                "UPDATE subscriptions SET status = 'expired' WHERE id IN ($placeholders)"
            );
            $update->execute($ids);
        }

        return $ids;
    }

    /** Admin monitoring list with restaurant names, optional filter. */
    public static function adminListing(?string $status = null): array
    {
        $sql = 'SELECT s.*, r.name AS restaurant_name
                  FROM subscriptions s
                  JOIN restaurants r ON r.id = s.restaurant_id
                 WHERE 1 = 1';
        $params = [];

        if (in_array($status, ['active', 'expired', 'cancelled'], true)) {
            $sql .= ' AND s.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY s.id DESC';

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
