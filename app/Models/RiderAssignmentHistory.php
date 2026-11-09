<?php

namespace App\Models;

/**
 * Data access for rider_assignment_history — EVERY assignment attempt
 * is recorded (assigned, accepted, rejected, reassigned). Reassignment
 * never overwrites the previous attempt.
 */
class RiderAssignmentHistory extends Model
{
    public static function create(
        int $fulfilmentId,
        int $riderId,
        int $assignedBy,
        string $status = 'Assigned'
    ): int {
        $stmt = self::db()->prepare(
            'INSERT INTO rider_assignment_history (fulfilment_id, rider_id, assigned_by, status)
             VALUES (:fid, :rid, :aid, :status)'
        );
        $stmt->execute([
            'fid'    => $fulfilmentId,
            'rid'    => $riderId,
            'aid'    => $assignedBy,
            'status' => $status,
        ]);
        return (int) self::db()->lastInsertId();
    }

    /** Record the rider's response (accept / reject) on the latest attempt. */
    public static function recordResponse(int $historyId, string $status, ?string $reason = null): void
    {
        $stmt = self::db()->prepare(
            'UPDATE rider_assignment_history
                SET status = :status, responded_at = NOW(), rejection_reason = :reason
              WHERE id = :id'
        );
        $stmt->execute(['status' => $status, 'reason' => $reason, 'id' => $historyId]);
    }

    /** Full assignment trail of one fulfilment (newest first). */
    public static function forFulfilment(int $fulfilmentId): array
    {
        $stmt = self::db()->prepare(
            'SELECT h.*, u.name AS rider_name, a.name AS admin_name
               FROM rider_assignment_history h
               JOIN users u ON u.id = h.rider_id
               JOIN users a ON a.id = h.assigned_by
              WHERE h.fulfilment_id = :fid
              ORDER BY h.id DESC'
        );
        $stmt->execute(['fid' => $fulfilmentId]);
        return $stmt->fetchAll();
    }

    /** The latest assignment attempt of a fulfilment. */
    public static function latestForFulfilment(int $fulfilmentId): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT * FROM rider_assignment_history WHERE fulfilment_id = :fid ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute(['fid' => $fulfilmentId]);
        return $stmt->fetch() ?: null;
    }
}
