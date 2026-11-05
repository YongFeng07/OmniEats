<?php

namespace App\Models;

/**
 * Data access for the platform activity log (admin monitoring).
 */
class ActivityLog extends Model
{
    /** Most recent activity entries for one user (user detail page). */
    public static function latestForUser(int $userId, int $limit = 10): array
    {
        $sql = 'SELECT * FROM activity_logs WHERE user_id = :uid ORDER BY id DESC LIMIT ' . (int) $limit;
        $stmt = self::db()->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    /** Most recent platform activity with actor names (admin overview). */
    public static function latest(int $limit = 100): array
    {
        $sql = 'SELECT l.*, u.name AS actor_name
                  FROM activity_logs l
             LEFT JOIN users u ON u.id = l.user_id
              ORDER BY l.id DESC
                 LIMIT ' . (int) $limit;
        return self::db()->query($sql)->fetchAll();
    }
}
