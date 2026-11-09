<?php

namespace App\Models;

/**
 * Data access for rider_profiles (Delivery & Rider module — Student 3).
 * The account itself is a users row with the rider role; this table
 * stores rider-specific information (vehicle, availability, status).
 */
class RiderProfile extends Model
{
    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT rp.*, u.name, u.email, u.phone
               FROM rider_profiles rp
               JOIN users u ON u.id = rp.user_id
              WHERE rp.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findForUser(int $userId): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM rider_profiles WHERE user_id = :uid');
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetch() ?: null;
    }

    /** All riders with account info (admin rider management). */
    public static function all(): array
    {
        $stmt = self::db()->query(
            'SELECT rp.*, u.name, u.email, u.phone
               FROM rider_profiles rp
               JOIN users u ON u.id = rp.user_id
              ORDER BY rp.id ASC'
        );
        return $stmt->fetchAll();
    }

    /**
     * Riders eligible for a new assignment: account active, on shift,
     * and with no other active delivery assignment.
     */
    public static function availableForAssignment(): array
    {
        $stmt = self::db()->query(
            "SELECT rp.*, u.name, u.email, u.phone
               FROM rider_profiles rp
               JOIN users u ON u.id = rp.user_id
              WHERE rp.is_active = 1
                AND rp.is_available = 1
                AND u.is_active = 1
                AND NOT EXISTS (
                    SELECT 1 FROM fulfilments f
                     WHERE f.rider_id = rp.user_id
                       AND f.status IN ('assigned', 'accepted', 'picked_up', 'out_for_delivery')
                )
              ORDER BY rp.id ASC"
        );
        return $stmt->fetchAll();
    }

    public static function create(int $userId, string $vehicleType, string $vehiclePlate): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO rider_profiles (user_id, vehicle_type, vehicle_plate, is_active, is_available)
             VALUES (:uid, :type, :plate, 1, 0)'
        );
        $stmt->execute(['uid' => $userId, 'type' => $vehicleType, 'plate' => $vehiclePlate]);
        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, string $vehicleType, string $vehiclePlate): void
    {
        $stmt = self::db()->prepare(
            'UPDATE rider_profiles SET vehicle_type = :type, vehicle_plate = :plate WHERE id = :id'
        );
        $stmt->execute(['type' => $vehicleType, 'plate' => $vehiclePlate, 'id' => $id]);
    }

    /** Suspension toggle (rider DELETE = suspend, records preserved). */
    public static function setActive(int $id, bool $active): void
    {
        $stmt = self::db()->prepare(
            'UPDATE rider_profiles SET is_active = :a, is_available = 0 WHERE id = :id'
        );
        $stmt->execute(['a' => $active ? 1 : 0, 'id' => $id]);
    }

    /** Availability toggle (on shift / off shift). */
    public static function setAvailable(int $id, bool $available): void
    {
        $stmt = self::db()->prepare('UPDATE rider_profiles SET is_available = :a WHERE id = :id');
        $stmt->execute(['a' => $available ? 1 : 0, 'id' => $id]);
    }
}
