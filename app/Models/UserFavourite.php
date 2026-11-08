<?php

namespace App\Models;

/**
 * Data access for user_favourites — customer-saved restaurants
 * (composite primary key on user + restaurant).
 */
class UserFavourite extends Model
{
    /** Restaurants saved by a customer (approved ones, for display). */
    public static function restaurantsForUser(int $userId): array
    {
        $stmt = self::db()->prepare(
            'SELECT r.*
               FROM user_favourites uf
               JOIN restaurants r ON r.id = uf.restaurant_id
              WHERE uf.user_id = :uid
              ORDER BY uf.created_at DESC'
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public static function exists(int $userId, int $restaurantId): bool
    {
        $stmt = self::db()->prepare(
            'SELECT COUNT(*) FROM user_favourites WHERE user_id = :uid AND restaurant_id = :rid'
        );
        $stmt->execute(['uid' => $userId, 'rid' => $restaurantId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** Toggle a favourite; returns the new state (true = saved). */
    public static function toggle(int $userId, int $restaurantId): bool
    {
        if (self::exists($userId, $restaurantId)) {
            $stmt = self::db()->prepare(
                'DELETE FROM user_favourites WHERE user_id = :uid AND restaurant_id = :rid'
            );
            $stmt->execute(['uid' => $userId, 'rid' => $restaurantId]);
            return false;
        }

        $stmt = self::db()->prepare(
            'INSERT INTO user_favourites (user_id, restaurant_id) VALUES (:uid, :rid)'
        );
        $stmt->execute(['uid' => $userId, 'rid' => $restaurantId]);
        return true;
    }
}
