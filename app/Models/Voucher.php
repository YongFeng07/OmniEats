<?php

namespace App\Models;

/**
 * Data access for vouchers (Promotion & Voucher module — Student 1).
 * Vouchers are platform-wide (restaurant_id NULL) or tenant-specific.
 */
class Voucher extends Model
{
    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM vouchers WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByCode(string $code): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM vouchers WHERE code = :code');
        $stmt->execute(['code' => $code]);
        return $stmt->fetch() ?: null;
    }

    /** Admin listing with optional search + active filter. */
    public static function listing(?string $q = null, ?string $active = null): array
    {
        $sql = 'SELECT v.*, r.name AS restaurant_name
                  FROM vouchers v
             LEFT JOIN restaurants r ON r.id = v.restaurant_id
                 WHERE 1 = 1';
        $params = [];

        if ($q !== null && $q !== '') {
            $sql .= ' AND (v.code LIKE :q1 OR v.title LIKE :q2)';
            $params['q1'] = $params['q2'] = '%' . $q . '%';
        }

        if ($active === 'active' || $active === 'inactive') {
            $sql .= ' AND v.is_active = :active';
            $params['active'] = $active === 'active' ? 1 : 0;
        }

        $sql .= ' ORDER BY v.id DESC';

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Vouchers belonging to ONE restaurant (owner management). */
    public static function listingForRestaurant(int $restaurantId): array
    {
        $stmt = self::db()->prepare(
            'SELECT * FROM vouchers WHERE restaurant_id = :rid ORDER BY id DESC'
        );
        $stmt->execute(['rid' => $restaurantId]);
        return $stmt->fetchAll();
    }

    /**
     * Vouchers a customer can currently see/choose for one restaurant
     * (Grab-style voucher tray): active, within the date window, not
     * exhausted, global or matching the restaurant.
     */
    public static function availableFor(int $restaurantId): array
    {
        $stmt = self::db()->prepare(
            'SELECT v.*, r.name AS restaurant_name
               FROM vouchers v
          LEFT JOIN restaurants r ON r.id = v.restaurant_id
              WHERE v.is_active = 1
                AND v.starts_at <= NOW()
                AND v.ends_at >= NOW()
                AND v.used_count < v.usage_limit
                AND (v.restaurant_id IS NULL OR v.restaurant_id = :rid)
              ORDER BY v.ends_at ASC'
        );
        $stmt->execute(['rid' => $restaurantId]);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO vouchers
               (code, title, discount_type, discount_value, min_order, max_discount,
                starts_at, ends_at, usage_limit, restaurant_id, is_active)
             VALUES
               (:code, :title, :type, :value, :min_order, :max_discount,
                :starts_at, :ends_at, :usage_limit, :restaurant_id, :is_active)'
        );
        $stmt->execute([
            'code'          => $data['code'],
            'title'         => $data['title'],
            'type'          => $data['discount_type'],
            'value'         => sprintf('%.2f', $data['discount_value']),
            'min_order'     => sprintf('%.2f', $data['min_order']),
            'max_discount'  => $data['max_discount'] !== null && $data['max_discount'] !== '' ? sprintf('%.2f', $data['max_discount']) : null,
            'starts_at'     => $data['starts_at'],
            'ends_at'       => $data['ends_at'],
            'usage_limit'   => (int) $data['usage_limit'],
            'restaurant_id' => !empty($data['restaurant_id']) ? (int) $data['restaurant_id'] : null,
            'is_active'     => (int) ($data['is_active'] ?? 1),
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = self::db()->prepare(
            'UPDATE vouchers
                SET code = :code, title = :title, discount_type = :type,
                    discount_value = :value, min_order = :min_order,
                    max_discount = :max_discount, starts_at = :starts_at,
                    ends_at = :ends_at, usage_limit = :usage_limit,
                    restaurant_id = :restaurant_id, is_active = :is_active
              WHERE id = :id'
        );
        $stmt->execute([
            'code'          => $data['code'],
            'title'         => $data['title'],
            'type'          => $data['discount_type'],
            'value'         => sprintf('%.2f', $data['discount_value']),
            'min_order'     => sprintf('%.2f', $data['min_order']),
            'max_discount'  => $data['max_discount'] !== null && $data['max_discount'] !== '' ? sprintf('%.2f', $data['max_discount']) : null,
            'starts_at'     => $data['starts_at'],
            'ends_at'       => $data['ends_at'],
            'usage_limit'   => (int) $data['usage_limit'],
            'restaurant_id' => !empty($data['restaurant_id']) ? (int) $data['restaurant_id'] : null,
            'is_active'     => (int) ($data['is_active'] ?? 1),
            'id'            => $id,
        ]);
    }

    /** Soft archive / restore (voucher DELETE) — redemptions preserved. */
    public static function setActive(int $id, bool $active): void
    {
        $stmt = self::db()->prepare('UPDATE vouchers SET is_active = :a WHERE id = :id');
        $stmt->execute(['a' => $active ? 1 : 0, 'id' => $id]);
    }

    /** Increment the redemption counter (called inside the checkout transaction). */
    public static function incrementUsed(int $id): void
    {
        $stmt = self::db()->prepare(
            'UPDATE vouchers SET used_count = used_count + 1 WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }
}
