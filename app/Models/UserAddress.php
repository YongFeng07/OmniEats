<?php

namespace App\Models;

/**
 * Data access for user_addresses — the customer address book used by
 * the checkout picker (Module 5 convenience, customer-owned rows).
 */
class UserAddress extends Model
{
    public static function allForUser(int $userId): array
    {
        $stmt = self::db()->prepare(
            'SELECT * FROM user_addresses WHERE user_id = :uid ORDER BY is_default DESC, id DESC'
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM user_addresses WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $userId, string $label, string $address, string $phone, bool $isDefault): int
    {
        if ($isDefault) {
            self::clearDefault($userId);
        }

        $stmt = self::db()->prepare(
            'INSERT INTO user_addresses (user_id, label, address, phone, is_default)
             VALUES (:uid, :label, :address, :phone, :def)'
        );
        $stmt->execute([
            'uid'     => $userId,
            'label'   => $label,
            'address' => $address,
            'phone'   => $phone,
            'def'     => $isDefault ? 1 : 0,
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function delete(int $id, int $userId): void
    {
        $stmt = self::db()->prepare('DELETE FROM user_addresses WHERE id = :id AND user_id = :uid');
        $stmt->execute(['id' => $id, 'uid' => $userId]);
    }

    public static function setDefault(int $id, int $userId): void
    {
        self::clearDefault($userId);
        $stmt = self::db()->prepare(
            'UPDATE user_addresses SET is_default = 1 WHERE id = :id AND user_id = :uid'
        );
        $stmt->execute(['id' => $id, 'uid' => $userId]);
    }

    private static function clearDefault(int $userId): void
    {
        $stmt = self::db()->prepare('UPDATE user_addresses SET is_default = 0 WHERE user_id = :uid');
        $stmt->execute(['uid' => $userId]);
    }
}
