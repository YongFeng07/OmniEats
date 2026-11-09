<?php

namespace App\Models;

/**
 * Data access for reviews (customer ratings of completed orders).
 * One review per order (UNIQUE order_id); restaurants.rating is
 * recomputed from these rows after every submission.
 */
class Review extends Model
{
    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM reviews WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByOrder(int $orderId): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM reviews WHERE order_id = :oid');
        $stmt->execute(['oid' => $orderId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Latest VISIBLE reviews for one restaurant (public restaurant page).
     * Admin-hidden reviews are excluded here; staff/admin views use
     * allForRestaurant() which keeps them flagged.
     */
    public static function listForRestaurant(int $restaurantId, int $limit = 5): array
    {
        $stmt = self::db()->prepare(
            'SELECT r.*, u.name AS customer_name
               FROM reviews r
               JOIN users u ON u.id = r.customer_id
              WHERE r.restaurant_id = :rid
                AND r.is_hidden = 0
              ORDER BY r.id DESC
              LIMIT ' . max(1, min(20, $limit))
        );
        $stmt->execute(['rid' => $restaurantId]);
        return $stmt->fetchAll();
    }

    /** ALL reviews for one restaurant incl. hidden (staff/admin). */
    public static function allForRestaurant(int $restaurantId): array
    {
        $stmt = self::db()->prepare(
            'SELECT r.*, u.name AS customer_name
               FROM reviews r
               JOIN users u ON u.id = r.customer_id
              WHERE r.restaurant_id = :rid
              ORDER BY r.id DESC'
        );
        $stmt->execute(['rid' => $restaurantId]);
        return $stmt->fetchAll();
    }

    /** Every review platform-wide with restaurant names (admin moderation). */
    public static function allWithRestaurants(): array
    {
        $stmt = self::db()->query(
            'SELECT r.*, u.name AS customer_name, res.name AS restaurant_name
               FROM reviews r
               JOIN users u ON u.id = r.customer_id
               JOIN restaurants res ON res.id = r.restaurant_id
              ORDER BY r.id DESC'
        );
        return $stmt->fetchAll();
    }

    /** Restaurant staff reply to a review (M2 — review management). */
    public static function setReply(int $id, string $reply, int $repliedBy): void
    {
        $stmt = self::db()->prepare(
            'UPDATE reviews SET reply = :reply, replied_by = :uid, replied_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['reply' => $reply, 'uid' => $repliedBy, 'id' => $id]);
    }

    /** Admin moderation: hide / unhide an inappropriate review. */
    public static function setHidden(int $id, bool $hidden, int $hiddenBy): void
    {
        $stmt = self::db()->prepare(
            'UPDATE reviews SET is_hidden = :h, hidden_by = :uid, hidden_at = ' . ($hidden ? 'NOW()' : 'NULL') . ' WHERE id = :id'
        );
        $stmt->execute(['h' => $hidden ? 1 : 0, 'uid' => $hiddenBy, 'id' => $id]);
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

    /** Reviews written by one customer (profile page). */
    public static function listForCustomer(int $customerId): array
    {
        $stmt = self::db()->prepare(
            'SELECT r.*, res.name AS restaurant_name
               FROM reviews r
               JOIN restaurants res ON res.id = r.restaurant_id
              WHERE r.customer_id = :cid
              ORDER BY r.id DESC
              LIMIT 5'
        );
        $stmt->execute(['cid' => $customerId]);
        return $stmt->fetchAll();
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
