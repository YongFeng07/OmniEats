<?php

namespace App\Models;

use PDO;

/**
 * Data access for the users / user_roles tables (Module 1).
 * All queries use prepared statements.
 */
class User extends Model
{
    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    /** Role names assigned to a user, e.g. ['customer']. */
    public static function roles(int $userId): array
    {
        $stmt = self::db()->prepare(
            'SELECT r.name
               FROM user_roles ur
               JOIN roles r ON r.id = ur.role_id
              WHERE ur.user_id = :uid'
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /** The restaurant a user belongs to (tenant link), or null. */
    public static function tenantId(int $userId): ?int
    {
        $stmt = self::db()->prepare('SELECT restaurant_id FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $value = $stmt->fetchColumn();
        return $value === false ? null : (int) $value;
    }
}
