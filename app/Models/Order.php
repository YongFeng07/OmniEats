<?php

namespace App\Models;

/**
 * Data access for orders (Module 5). Order totals are always computed
 * on the backend; order_items carry the historical name/price
 * snapshots.
 */
class Order extends Model
{
    /** Human-readable, unique order number, e.g. OE-20261105-3F9A. */
    public static function nextOrderNo(): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $no = 'OE-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
            $stmt = self::db()->prepare('SELECT COUNT(*) FROM orders WHERE order_no = :no');
            $stmt->execute(['no' => $no]);
            if ((int) $stmt->fetchColumn() === 0) {
                return $no;
            }
        }
        throw new \RuntimeException('Could not generate a unique order number.');
    }

    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM orders WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO orders
               (order_no, customer_id, restaurant_id, branch_id, fulfilment_type,
                delivery_address, delivery_phone, subtotal, delivery_fee, discount, total, status, note)
             VALUES
               (:no, :customer, :restaurant, :branch, :type,
                :address, :phone, :subtotal, :fee, :discount, :total, :status, :note)'
        );
        $stmt->execute([
            'no'         => $data['order_no'],
            'customer'   => (int) $data['customer_id'],
            'restaurant' => (int) $data['restaurant_id'],
            'branch'     => $data['branch_id'] !== null ? (int) $data['branch_id'] : null,
            'type'       => $data['fulfilment_type'],
            'address'    => $data['delivery_address'],
            'phone'      => $data['delivery_phone'],
            'subtotal'   => sprintf('%.2f', $data['subtotal']),
            'fee'        => sprintf('%.2f', $data['delivery_fee']),
            'discount'   => sprintf('%.2f', $data['discount'] ?? 0.0),
            'total'      => sprintf('%.2f', $data['total']),
            'status'     => 'pending_payment',
            'note'       => $data['note'] !== '' ? $data['note'] : null,
        ]);
        return (int) self::db()->lastInsertId();
    }

    /** A customer's orders, newest first (with restaurant names). */
    public static function listForCustomer(int $customerId): array
    {
        $stmt = self::db()->prepare(
            'SELECT o.*, r.name AS restaurant_name,
                    (SELECT SUM(oi.quantity) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
               FROM orders o
               JOIN restaurants r ON r.id = o.restaurant_id
              WHERE o.customer_id = :cid
              ORDER BY o.id DESC'
        );
        $stmt->execute(['cid' => $customerId]);
        return $stmt->fetchAll();
    }

    /** A restaurant's orders (tenant), newest first, optional status filter. */
    public static function listForRestaurant(int $restaurantId, ?string $status = null): array
    {
        $sql = 'SELECT o.*, u.name AS customer_name,
                       (SELECT SUM(oi.quantity) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
                  FROM orders o
                  JOIN users u ON u.id = o.customer_id
                 WHERE o.restaurant_id = :rid';
        $params = ['rid' => $restaurantId];

        if (in_array($status, self::statuses(), true)) {
            $sql .= ' AND o.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY o.id DESC';

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function setStatus(int $id, string $status): void
    {
        $stmt = self::db()->prepare('UPDATE orders SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    /** Total orders across the platform (admin dashboard stat). */
    public static function countAll(): int
    {
        return (int) self::db()->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    }

    /** Orders created today for one restaurant — merchant dashboard stat. */
    public static function countTodayForRestaurant(int $restaurantId): int
    {
        $stmt = self::db()->prepare(
            'SELECT COUNT(*) FROM orders WHERE restaurant_id = :rid AND DATE(created_at) = CURDATE()'
        );
        $stmt->execute(['rid' => $restaurantId]);
        return (int) $stmt->fetchColumn();
    }

    /** The complete set of order statuses (see docs/DATABASE_DESIGN.md §7). */
    public static function statuses(): array
    {
        return ['pending_payment', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled'];
    }
}
