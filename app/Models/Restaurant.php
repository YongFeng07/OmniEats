<?php

namespace App\Models;

/**
 * Data access for the restaurants table (Module 2 — Restaurant &
 * Branch Management). Every query uses prepared statements; queries
 * that expose tenant data take an explicit restaurant id filter.
 */
class Restaurant extends Model
{
    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM restaurants WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM restaurants WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);
        return $stmt->fetch() ?: null;
    }

    /** The restaurant owned by a user account, or null. */
    public static function findByOwner(int $ownerId): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM restaurants WHERE owner_id = :oid');
        $stmt->execute(['oid' => $ownerId]);
        return $stmt->fetch() ?: null;
    }

    /** All restaurants (admin dropdowns). */
    public static function all(): array
    {
        return self::db()->query('SELECT id, name, status FROM restaurants ORDER BY name')->fetchAll();
    }

    /** Restaurants in one approval status (admin dashboard stat). */
    public static function countByStatus(string $status): int
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) FROM restaurants WHERE status = :status');
        $stmt->execute(['status' => $status]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Public listing: APPROVED restaurants only, with optional free-text
     * search and an open/closed filter (availability control feature).
     */
    public static function publicListing(?string $q = null, ?string $openFilter = null): array
    {
        $sql = 'SELECT r.*,
                       (SELECT COUNT(*) FROM restaurant_branches b
                         WHERE b.restaurant_id = r.id) AS branch_count
                  FROM restaurants r
                 WHERE r.status = :status';
        $params = ['status' => 'approved'];

        if ($q !== null && $q !== '') {
            // Unique placeholder per use: PDO native prepares (emulation
            // off) reject a named placeholder that appears more than once.
            $sql .= ' AND (r.name LIKE :q1 OR r.description LIKE :q2 OR r.city LIKE :q3)';
            $params['q1'] = $params['q2'] = $params['q3'] = '%' . $q . '%';
        }

        if ($openFilter === 'open' || $openFilter === 'closed') {
            $sql .= ' AND r.is_open = :open';
            $params['open'] = $openFilter === 'open' ? 1 : 0;
        }

        $sql .= ' ORDER BY r.name ASC';

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Admin listing: all restaurants with owner details and branch
     * counts, searchable and filterable by approval status.
     */
    public static function adminListing(?string $q = null, ?string $status = null): array
    {
        $sql = 'SELECT r.*,
                       u.name AS owner_name,
                       u.email AS owner_email,
                       (SELECT COUNT(*) FROM restaurant_branches b
                         WHERE b.restaurant_id = r.id) AS branch_count
                  FROM restaurants r
                  JOIN users u ON u.id = r.owner_id
                 WHERE 1 = 1';
        $params = [];

        if ($q !== null && $q !== '') {
            // Unique placeholders — see publicListing().
            $sql .= ' AND (r.name LIKE :q1 OR u.name LIKE :q2 OR u.email LIKE :q3)';
            $params['q1'] = $params['q2'] = $params['q3'] = '%' . $q . '%';
        }

        if (in_array($status, ['pending', 'approved', 'deactivated'], true)) {
            $sql .= ' AND r.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY r.id ASC';

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function create(array $data, int $ownerId): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO restaurants
               (owner_id, name, slug, description, logo, cover_image, phone, email,
                address, city, state, postcode, opening_hours,
                delivery_minutes, free_delivery)
             VALUES
               (:owner, :name, :slug, :description, :logo, :cover, :phone, :email,
                :address, :city, :state, :postcode, :hours,
                :delivery_minutes, :free_delivery)'
        );
        $stmt->execute([
            'owner'       => $ownerId,
            'name'        => $data['name'],
            'slug'        => $data['slug'],
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'logo'        => $data['logo'],
            'cover'       => $data['cover_image'],
            'phone'       => $data['phone'],
            'email'       => $data['email'] !== '' ? $data['email'] : null,
            'address'     => $data['address'],
            'city'        => $data['city'],
            'state'       => $data['state'],
            'postcode'    => $data['postcode'],
            'hours'       => $data['opening_hours'],
            // rating stays NULL until the platform has real ratings;
            // delivery_minutes / free_delivery are set by the owner.
            'delivery_minutes' => $data['delivery_minutes'] !== '' ? (int) $data['delivery_minutes'] : null,
            'free_delivery'    => (int) ($data['free_delivery'] ?? 0),
        ]);
        return (int) self::db()->lastInsertId();
    }

    /** Profile fields only — approval status and is_open change via their own methods. */
    public static function update(int $id, array $data, ?string $logo, ?string $coverImage): void
    {
        $stmt = self::db()->prepare(
            'UPDATE restaurants
                SET name = :name, description = :description, phone = :phone, email = :email,
                    address = :address, city = :city, state = :state, postcode = :postcode,
                    opening_hours = :hours, logo = :logo, cover_image = :cover,
                    delivery_minutes = :delivery_minutes, free_delivery = :free_delivery
              WHERE id = :id'
        );
        $stmt->execute([
            'name'        => $data['name'],
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'phone'       => $data['phone'],
            'email'       => $data['email'] !== '' ? $data['email'] : null,
            'address'     => $data['address'],
            'city'        => $data['city'],
            'state'       => $data['state'],
            'postcode'    => $data['postcode'],
            'hours'       => $data['opening_hours'],
            'logo'        => $logo,
            'cover'       => $coverImage,
            'delivery_minutes' => $data['delivery_minutes'] !== '' ? (int) $data['delivery_minutes'] : null,
            'free_delivery'    => (int) ($data['free_delivery'] ?? 0),
            'id'          => $id,
        ]);
    }

    /** Approval lifecycle: pending → approved / deactivated. */
    public static function setStatus(int $id, string $status): void
    {
        $stmt = self::db()->prepare('UPDATE restaurants SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    /** Availability control (unique feature): OPEN (1) / CLOSED (0). */
    public static function setOpen(int $id, bool $open): void
    {
        $stmt = self::db()->prepare('UPDATE restaurants SET is_open = :open WHERE id = :id');
        $stmt->execute(['open' => $open ? 1 : 0, 'id' => $id]);
    }
}
