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
}
