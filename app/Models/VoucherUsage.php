<?php

namespace App\Models;

/**
 * Data access for voucher_usages — the redemption audit trail.
 * One use per order per voucher (UNIQUE voucher_id + order_id).
 */
class VoucherUsage extends Model
{
    public static function create(int $voucherId, int $orderId, int $customerId, float $discount): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO voucher_usages (voucher_id, order_id, customer_id, discount_amount)
             VALUES (:vid, :oid, :cid, :discount)'
        );
        $stmt->execute([
            'vid'      => $voucherId,
            'oid'      => $orderId,
            'cid'      => $customerId,
            'discount' => sprintf('%.2f', $discount),
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function findForOrder(int $orderId): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT u.*, v.code, v.title FROM voucher_usages u
               JOIN vouchers v ON v.id = u.voucher_id
              WHERE u.order_id = :oid'
        );
        $stmt->execute(['oid' => $orderId]);
        return $stmt->fetch() ?: null;
    }

    /** Has this voucher already been redeemed by this customer? */
    public static function usedByCustomer(int $voucherId, int $customerId): bool
    {
        $stmt = self::db()->prepare(
            'SELECT COUNT(*) FROM voucher_usages WHERE voucher_id = :vid AND customer_id = :cid'
        );
        $stmt->execute(['vid' => $voucherId, 'cid' => $customerId]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
