<?php

namespace App\Models;

/**
 * Data access for subscription_plans (Module 4). Plans are archived
 * (is_active = 0), never hard-deleted — subscription history must keep
 * its references intact.
 */
class Plan extends Model
{
    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM subscription_plans WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByName(string $name): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM subscription_plans WHERE name = :name');
        $stmt->execute(['name' => $name]);
        return $stmt->fetch() ?: null;
    }

    /** Plans a restaurant may choose from (active only). */
    public static function allActive(): array
    {
        return self::db()->query(
            'SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
        )->fetchAll();
    }

    /** Admin listing with the number of active subscribers per plan. */
    public static function adminListing(): array
    {
        $stmt = self::db()->prepare(
            'SELECT p.*,
                    (SELECT COUNT(*) FROM subscriptions s
                      WHERE s.plan_id = p.id AND s.status = :status) AS active_subscribers
               FROM subscription_plans p
              ORDER BY p.sort_order ASC, p.id ASC'
        );
        $stmt->execute(['status' => 'active']);
        return $stmt->fetchAll();
    }

    /** How many active subscriptions currently use a plan. */
    public static function countActiveSubscriptions(int $planId): int
    {
        $stmt = self::db()->prepare(
            'SELECT COUNT(*) FROM subscriptions WHERE plan_id = :pid AND status = :status'
        );
        $stmt->execute(['pid' => $planId, 'status' => 'active']);
        return (int) $stmt->fetchColumn();
    }

    public static function create(array $data): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO subscription_plans
               (name, price, billing_cycle, max_branches, max_food_items, description, features, sort_order)
             VALUES (:name, :price, :cycle, :branches, :items, :description, :features, :sort)'
        );
        $stmt->execute([
            'name'        => $data['name'],
            'price'       => sprintf('%.2f', (float) $data['price']),
            'cycle'       => 'monthly',
            'branches'    => (int) $data['max_branches'],
            'items'       => $data['max_food_items'] !== '' ? (int) $data['max_food_items'] : null,
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'features'    => $data['features'] !== '' ? $data['features'] : null,
            'sort'        => (int) ($data['sort_order'] ?? 0),
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = self::db()->prepare(
            'UPDATE subscription_plans
                SET name = :name, price = :price, max_branches = :branches,
                    max_food_items = :items, description = :description,
                    features = :features, sort_order = :sort
              WHERE id = :id'
        );
        $stmt->execute([
            'name'        => $data['name'],
            'price'       => sprintf('%.2f', (float) $data['price']),
            'branches'    => (int) $data['max_branches'],
            'items'       => $data['max_food_items'] !== '' ? (int) $data['max_food_items'] : null,
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'features'    => $data['features'] !== '' ? $data['features'] : null,
            'sort'        => (int) ($data['sort_order'] ?? 0),
            'id'          => $id,
        ]);
    }

    /** Archive (is_active = 0) or restore a plan. */
    public static function setActive(int $id, bool $active): void
    {
        $stmt = self::db()->prepare('UPDATE subscription_plans SET is_active = :active WHERE id = :id');
        $stmt->execute(['active' => $active ? 1 : 0, 'id' => $id]);
    }
}
