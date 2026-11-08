<?php

namespace App\Models;

/**
 * Data access for reviews (customer ratings of completed orders).
 * One review per order (UNIQUE order_id); restaurants.rating is
 * recomputed from these rows after every submission.
 */
class Review extends Model
{
    public static function findByOrder(int $orderId): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM reviews WHERE order_id = :oid');
        $stmt->execute(['oid' => $orderId]);
        return $stmt->fetch() ?: null;
    }

    /** Latest reviews for one restaurant (public restaurant page). */
    public static function listForRestaurant(int $restaurantId, int $limit = 5): array
    {
        $stmt = self::db()->prepare(
            'SELECT r.*, u.name AS customer_name
               FROM reviews r
               JOIN users u ON u.id = r.customer_id
              WHERE r.restaurant_id = :rid
              ORDER BY r.id DESC
              LIMIT ' . max(1, min(20, $limit))
        );
        $stmt->execute(['rid' => $restaurantId]);
        return $stmt->fetchAll();
    }

    /** Average rating for a restaurant, or null when it has no reviews. */
    public static function averageForRestaurant(int $restaurantId): ?float
    {
        $stmt = self::db()->prepare(
            'SELECT AVG(rating) FROM reviews WHERE restaurant_id = :rid'
        );
        $stmt->execute(['rid' => $restaurantId]);
        $value = $stmt->fetchColumn();
        return $value !== null && $value !== false ? round((float) $value, 1) : null;
    }

    /** Number of reviews for a restaurant (shown next to the rating). */
    public static function countForRestaurant(int $restaurantId): int
    {
        $stmt = self::db()->prepare(
            'SELECT COUNT(*) FROM reviews WHERE restaurant_id = :rid'
        );
        $stmt->execute(['rid' => $restaurantId]);
        return (int) $stmt->fetchColumn();
    }

    public static function create(int $orderId, int $customerId, int $restaurantId, int $rating, ?string $comment): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO reviews (order_id, customer_id, restaurant_id, rating, comment)
             VALUES (:oid, :cid, :rid, :rating, :comment)'
        );
        $stmt->execute([
            'oid'     => $orderId,
            'cid'     => $customerId,
            'rid'     => $restaurantId,
            'rating'  => $rating,
            'comment' => $comment !== null && $comment !== '' ? $comment : null,
        ]);
        return (int) self::db()->lastInsertId();
    }
}
