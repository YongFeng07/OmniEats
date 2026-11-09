<?php

namespace App\Models;

/**
 * Data access for user_addresses — the customer address book (Customer
 * Profile module — Student 2). Customers manage only their own rows;
 * the checkout picker reads this table.
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

    public static function create(
        int $userId,
        string $label,
        string $recipientName,
        string $address,
        ?string $city,
        ?string $state,
        ?string $postcode,
        string $phone,
        bool $isDefault
    ): int {
        if ($isDefault) {
            self::clearDefault($userId);
        }

        $stmt = self::db()->prepare(
            'INSERT INTO user_addresses
               (user_id, label, recipient_name, address, city, state, postcode, phone, is_default)
             VALUES
               (:uid, :label, :recipient, :address, :city, :state, :postcode, :phone, :def)'
        );
        $stmt->execute([
            'uid'       => $userId,
            'label'     => $label,
            'recipient' => $recipientName,
            'address'   => $address,
            'city'      => $city !== '' && $city !== null ? $city : null,
            'state'     => $state !== '' && $state !== null ? $state : null,
            'postcode'  => $postcode !== '' && $postcode !== null ? $postcode : null,
            'phone'     => $phone,
            'def'       => $isDefault ? 1 : 0,
        ]);
        return (int) self::db()->lastInsertId();
    }

    /** UPDATE (Customer Profile CRUD) — all editable fields. */
    public static function update(
        int $id,
        int $userId,
        string $label,
        string $recipientName,
        string $address,
        ?string $city,
        ?string $state,
        ?string $postcode,
        string $phone
    ): void {
        $stmt = self::db()->prepare(
            'UPDATE user_addresses
                SET label = :label, recipient_name = :recipient, address = :address,
                    city = :city, state = :state, postcode = :postcode, phone = :phone
              WHERE id = :id AND user_id = :uid'
        );
        $stmt->execute([
            'label'     => $label,
            'recipient' => $recipientName,
            'address'   => $address,
            'city'      => $city !== '' && $city !== null ? $city : null,
            'state'     => $state !== '' && $state !== null ? $state : null,
            'postcode'  => $postcode !== '' && $postcode !== null ? $postcode : null,
            'phone'     => $phone,
            'id'        => $id,
            'uid'       => $userId,
        ]);
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
