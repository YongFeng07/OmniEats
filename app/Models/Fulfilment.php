<?php

namespace App\Models;

/**
 * Data access for fulfilments (Module 6): delivery / self-pickup
 * execution records, one per order (UNIQUE order_id).
 */
class Fulfilment extends Model
{
    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM fulfilments WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByOrder(int $orderId): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM fulfilments WHERE order_id = :oid');
        $stmt->execute(['oid' => $orderId]);
        return $stmt->fetch() ?: null;
    }

    /** Created automatically when a payment succeeds. */
    public static function create(int $orderId, string $type, ?string $address, ?string $phone): int
    {
        // Delivery fulfilments start the rider-assignment workflow;
        // pickup fulfilments start at plain "pending".
        $status = $type === 'delivery' ? 'pending_assignment' : 'pending';

        $stmt = self::db()->prepare(
            'INSERT INTO fulfilments (order_id, type, address, phone, status)
             VALUES (:oid, :type, :address, :phone, :status)'
        );
        $stmt->execute([
            'oid'     => $orderId,
            'type'    => $type,
            'address' => $address,
            'phone'   => $phone,
            'status'  => $status,
        ]);
        return (int) self::db()->lastInsertId();
    }

    /** Admin assigns a rider (the history row is written by the service). */
    public static function assignRider(int $id, int $riderId, int $assignedBy): void
    {
        $stmt = self::db()->prepare(
            'UPDATE fulfilments
                SET rider_id = :rid, assigned_by = :aid, assigned_at = NOW(),
                    accepted_at = NULL, rejected_at = NULL, rejection_reason = NULL,
                    status = :status
              WHERE id = :id'
        );
        $stmt->execute(['rid' => $riderId, 'aid' => $assignedBy, 'status' => 'assigned', 'id' => $id]);
    }

    /** A rejected assignment returns the delivery to admin attention. */
    public static function clearAssignment(int $id): void
    {
        $stmt = self::db()->prepare(
            'UPDATE fulfilments
                SET rider_id = NULL, assigned_by = NULL, assigned_at = NULL,
                    accepted_at = NULL, rejected_at = NOW(), status = :status
              WHERE id = :id'
        );
        $stmt->execute(['status' => 'pending_assignment', 'id' => $id]);
    }

    /** Rider accepts the assignment. */
    public static function markAccepted(int $id): void
    {
        $stmt = self::db()->prepare(
            'UPDATE fulfilments SET status = :s, accepted_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['s' => 'accepted', 'id' => $id]);
    }

    /** Rider confirms food pickup. */
    public static function markPickedUp(int $id): void
    {
        $stmt = self::db()->prepare(
            'UPDATE fulfilments SET status = :s, picked_up_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['s' => 'picked_up', 'id' => $id]);
    }

    /** Rider is on the road. */
    public static function markOutForDelivery(int $id): void
    {
        $stmt = self::db()->prepare(
            'UPDATE fulfilments SET status = :s, out_for_delivery_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['s' => 'out_for_delivery', 'id' => $id]);
    }

    /** Rider confirms delivery. */
    public static function markDelivered(int $id): void
    {
        $stmt = self::db()->prepare(
            "UPDATE fulfilments
                SET status = :s, delivered_at = NOW(), completed_at = NOW()
              WHERE id = :id"
        );
        $stmt->execute(['s' => 'delivered', 'id' => $id]);
    }

    /**
     * Deliveries waiting for admin rider assignment: delivery type,
     * unassigned, and the order has reached "ready".
     */
    public static function awaitingAssignment(): array
    {
        $stmt = self::db()->prepare(
            'SELECT f.*, o.order_no, u.name AS customer_name
               FROM fulfilments f
               JOIN orders o ON o.id = f.order_id
               JOIN users u ON u.id = o.customer_id
              WHERE f.type = :type AND f.status = :status AND o.status = :order_status
              ORDER BY f.id ASC'
        );
        $stmt->execute(['type' => 'delivery', 'status' => 'pending_assignment', 'order_status' => 'ready']);
        return $stmt->fetchAll();
    }

    /** One rider's currently active delivery, or null. */
    public static function activeForRider(int $riderId): ?array
    {
        $stmt = self::db()->prepare(
            "SELECT f.*, o.order_no, o.delivery_address, o.recipient_name, o.delivery_phone
               FROM fulfilments f
               JOIN orders o ON o.id = f.order_id
              WHERE f.rider_id = :rid
                AND f.status IN ('assigned', 'accepted', 'picked_up', 'out_for_delivery')
              ORDER BY f.id DESC
              LIMIT 1"
        );
        $stmt->execute(['rid' => $riderId]);
        return $stmt->fetch() ?: null;
    }

    /** A rider's delivery history (delivered / cancelled / rejected). */
    public static function historyForRider(int $riderId): array
    {
        $stmt = self::db()->prepare(
            'SELECT f.*, o.order_no, o.restaurant_id, r.name AS restaurant_name,
                    (SELECT SUM(oi.quantity) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
               FROM fulfilments f
               JOIN orders o ON o.id = f.order_id
               JOIN restaurants r ON r.id = o.restaurant_id
              WHERE f.rider_id = :rid
                AND f.status IN (:s1, :s2)
              ORDER BY f.id DESC'
        );
        $stmt->execute(['rid' => $riderId, 's1' => 'delivered', 's2' => 'cancelled']);
        return $stmt->fetchAll();
    }

    public static function setStatus(int $id, string $status): void
    {
        $stmt = self::db()->prepare(
            "UPDATE fulfilments
                SET status = :status, completed_at = " . ($status === 'completed' ? 'NOW()' : 'NULL') . "
              WHERE id = :id"
        );
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    /**
     * Physical delete — allowed ONLY for eligible pending/cancelled
     * records (never completed financial-execution records; those stay
     * as the audit trail). Eligibility is enforced in FulfilmentService.
     */
    public static function delete(int $id): void
    {
        $stmt = self::db()->prepare('DELETE FROM fulfilments WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /** A restaurant's fulfilment records (tenant), optional filter. */
    public static function listForRestaurant(int $restaurantId, ?string $status = null): array
    {
        $sql = 'SELECT f.*, o.order_no, o.fulfilment_type AS order_type, u.name AS customer_name
                  FROM fulfilments f
                  JOIN orders o ON o.id = f.order_id
                  JOIN users u ON u.id = o.customer_id
                 WHERE o.restaurant_id = :rid';
        $params = ['rid' => $restaurantId];

        if (in_array($status, ['pending', 'out_for_delivery', 'ready_for_collection', 'completed', 'cancelled'], true)) {
            $sql .= ' AND f.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY f.id DESC';

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
