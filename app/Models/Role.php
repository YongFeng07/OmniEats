<?php

namespace App\Models;

/**
 * Data access for the roles table (Module 1).
 */
class Role extends Model
{
    /** The four platform roles; whitelist for all role assignments. */
    public const NAMES = ['super_admin', 'restaurant_owner', 'restaurant_staff', 'customer'];

    public static function all(): array
    {
        return self::db()->query('SELECT * FROM roles ORDER BY id')->fetchAll();
    }

    public static function findByName(string $name): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM roles WHERE name = :name');
        $stmt->execute(['name' => $name]);
        return $stmt->fetch() ?: null;
    }
}
