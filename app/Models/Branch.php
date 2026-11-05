<?php

namespace App\Models;

/**
 * Data access for restaurant_branches (Module 2).
 */
class Branch extends Model
{
    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM restaurant_branches WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** All branches of one restaurant (tenant). */
    public static function allForRestaurant(int $restaurantId): array
    {
        $stmt = self::db()->prepare(
            'SELECT * FROM restaurant_branches WHERE restaurant_id = :rid ORDER BY is_main DESC, id ASC'
        );
        $stmt->execute(['rid' => $restaurantId]);
        return $stmt->fetchAll();
    }

    public static function countForRestaurant(int $restaurantId): int
    {
        $stmt = self::db()->prepare(
            'SELECT COUNT(*) FROM restaurant_branches WHERE restaurant_id = :rid'
        );
        $stmt->execute(['rid' => $restaurantId]);
        return (int) $stmt->fetchColumn();
    }

    public static function create(array $data, int $restaurantId): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO restaurant_branches (restaurant_id, name, address, phone, opening_hours)
             VALUES (:rid, :name, :address, :phone, :hours)'
        );
        $stmt->execute([
            'rid'     => $restaurantId,
            'name'    => $data['name'],
            'address' => $data['address'] !== '' ? $data['address'] : null,
            'phone'   => $data['phone'] !== '' ? $data['phone'] : null,
            'hours'   => $data['opening_hours'] !== '' ? $data['opening_hours'] : null,
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = self::db()->prepare(
            'UPDATE restaurant_branches
                SET name = :name, address = :address, phone = :phone, opening_hours = :hours
              WHERE id = :id'
        );
        $stmt->execute([
            'name'    => $data['name'],
            'address' => $data['address'] !== '' ? $data['address'] : null,
            'phone'   => $data['phone'] !== '' ? $data['phone'] : null,
            'hours'   => $data['opening_hours'] !== '' ? $data['opening_hours'] : null,
            'id'      => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = self::db()->prepare('DELETE FROM restaurant_branches WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
