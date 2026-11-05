<?php

namespace App\Models;

use PDO;

/**
 * Data access for the users / user_roles tables (Module 1).
 * All queries use prepared statements. Deletion is implemented as
 * soft deletion via the is_active flag (accounts referenced by order
 * history must never be hard-deleted).
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

    /**
     * Admin user list with role names and restaurant names attached.
     * Filters: free-text search (name/email), role name, active status.
     */
    public static function search(?string $q = null, ?string $role = null, ?string $status = null): array
    {
        $sql = 'SELECT u.*,
                       (SELECT GROUP_CONCAT(r.name ORDER BY r.id)
                          FROM user_roles ur
                          JOIN roles r ON r.id = ur.role_id
                         WHERE ur.user_id = u.id) AS role_names,
                       res.name AS restaurant_name
                  FROM users u
             LEFT JOIN restaurants res ON res.id = u.restaurant_id
                 WHERE 1 = 1';
        $params = [];

        if ($q !== null && $q !== '') {
            $sql .= ' AND (u.name LIKE :q OR u.email LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }

        if ($role !== null && $role !== '') {
            $sql .= ' AND EXISTS (SELECT 1
                                    FROM user_roles ur2
                                    JOIN roles r2 ON r2.id = ur2.role_id
                                   WHERE ur2.user_id = u.id AND r2.name = :role)';
            $params['role'] = $role;
        }

        if ($status === 'active' || $status === 'inactive') {
            $sql .= ' AND u.is_active = :status';
            $params['status'] = $status === 'active' ? 1 : 0;
        }

        $sql .= ' ORDER BY u.id ASC';

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function create(
        string $name,
        string $email,
        string $passwordHash,
        ?string $phone,
        ?int $restaurantId
    ): int {
        $stmt = self::db()->prepare(
            'INSERT INTO users (name, email, password_hash, phone, restaurant_id)
             VALUES (:name, :email, :hash, :phone, :rid)'
        );
        $stmt->execute([
            'name'  => $name,
            'email' => $email,
            'hash'  => $passwordHash,
            'phone' => $phone,
            'rid'   => $restaurantId,
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, string $name, string $email, ?string $phone): void
    {
        $stmt = self::db()->prepare(
            'UPDATE users SET name = :name, email = :email, phone = :phone WHERE id = :id'
        );
        $stmt->execute(['name' => $name, 'email' => $email, 'phone' => $phone, 'id' => $id]);
    }

    public static function updatePassword(int $id, string $passwordHash): void
    {
        $stmt = self::db()->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $stmt->execute(['hash' => $passwordHash, 'id' => $id]);
    }

    /** Replace a user's role assignments in one transaction. */
    public static function setRoles(int $userId, array $roleIds): void
    {
        $pdo = self::db();
        $pdo->beginTransaction();
        try {
            $delete = $pdo->prepare('DELETE FROM user_roles WHERE user_id = :uid');
            $delete->execute(['uid' => $userId]);

            $insert = $pdo->prepare(
                'INSERT INTO user_roles (user_id, role_id) VALUES (:uid, :rid)'
            );
            foreach ($roleIds as $roleId) {
                $insert->execute(['uid' => $userId, 'rid' => (int) $roleId]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Tenant link: which restaurant an owner/staff account belongs to. */
    public static function setTenant(int $userId, ?int $restaurantId): void
    {
        $stmt = self::db()->prepare('UPDATE users SET restaurant_id = :rid WHERE id = :id');
        $stmt->execute(['rid' => $restaurantId, 'id' => $userId]);
    }

    /** Soft delete / restore (is_active). */
    public static function setStatus(int $userId, bool $active): void
    {
        $stmt = self::db()->prepare('UPDATE users SET is_active = :active WHERE id = :id');
        $stmt->execute(['active' => $active ? 1 : 0, 'id' => $userId]);
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

    /** Total registered accounts (admin dashboard stat). */
    public static function count(): int
    {
        return (int) self::db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }
}
