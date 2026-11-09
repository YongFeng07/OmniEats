<?php

namespace App\Services;

use App\Models\Restaurant;
use App\Models\Voucher;
use App\Models\VoucherUsage;

/**
 * Promotion & Voucher business logic (Student 1 — Lim Li Zhe).
 *
 * ★ UNIQUE FEATURE — SMART VOUCHER VALIDATION:
 * A single validate() entry point checks EVERY eligibility rule —
 * active status, validity window, usage limit, minimum spending,
 * applicable restaurant — and returns a clear, specific result
 * together with the correct discount amount. The same check runs
 * when the voucher tray is shown, when the customer taps a voucher,
 * and AGAIN inside the checkout transaction (never trust the UI).
 */
class VoucherService
{
    /** Session key holding the customer's selected voucher id. */
    public const SESSION_KEY = 'voucher_id';

    /**
     * Smart validation + discount calculation.
     *
     * Returns ['ok' => true, 'voucher' => row, 'discount' => float]
     * or ['ok' => false, 'message' => clear reason].
     */
    public static function validate(int $voucherId, float $subtotal, int $restaurantId, ?int $customerId = null): array
    {
        $voucher = Voucher::find($voucherId);
        if ($voucher === null) {
            return ['ok' => false, 'message' => 'This voucher does not exist.'];
        }

        if ((int) $voucher['is_active'] !== 1) {
            return ['ok' => false, 'message' => 'This voucher is no longer available.'];
        }

        $now = time();
        if (strtotime($voucher['starts_at']) > $now) {
            return ['ok' => false, 'message' => 'This voucher is not valid yet.'];
        }
        if (strtotime($voucher['ends_at']) < $now) {
            return ['ok' => false, 'message' => 'This voucher has expired.'];
        }

        if ((int) $voucher['used_count'] >= (int) $voucher['usage_limit']) {
            return ['ok' => false, 'message' => 'This voucher has reached its usage limit.'];
        }

        if ($voucher['restaurant_id'] !== null && (int) $voucher['restaurant_id'] !== $restaurantId) {
            return ['ok' => false, 'message' => 'This voucher is only valid at a specific restaurant.'];
        }

        if ($subtotal < (float) $voucher['min_order']) {
            return [
                'ok'      => false,
                'message' => 'Minimum order of RM ' . number_format((float) $voucher['min_order'], 2) . ' is required for this voucher.',
            ];
        }

        $discount = self::calculateDiscount($voucher, $subtotal);

        return ['ok' => true, 'voucher' => $voucher, 'discount' => $discount];
    }

    /**
     * The discount math: fixed = the amount; percent = subtotal × %,
     * capped by max_discount and never more than the subtotal itself.
     */
    public static function calculateDiscount(array $voucher, float $subtotal): float
    {
        if ($voucher['discount_type'] === 'percent') {
            $discount = $subtotal * ((float) $voucher['discount_value'] / 100);
            if ($voucher['max_discount'] !== null) {
                $discount = min($discount, (float) $voucher['max_discount']);
            }
        } else {
            $discount = (float) $voucher['discount_value'];
        }

        return max(0.0, min($discount, $subtotal));
    }

    /** The vouchers eligible for a cart (Grab-style voucher tray). */
    public static function trayFor(int $restaurantId, float $subtotal): array
    {
        $rows = Voucher::availableFor($restaurantId);

        $tray = [];
        foreach ($rows as $voucher) {
            $result = self::validate((int) $voucher['id'], $subtotal, $restaurantId);
            $tray[] = $voucher + [
                'eligible'  => $result['ok'],
                'reason'    => $result['ok'] ? null : $result['message'],
                'discount'  => $result['ok'] ? $result['discount'] : 0.0,
            ];
        }
        return $tray;
    }

    /** Apply a voucher to the session cart (customer taps it). */
    public static function apply(int $voucherId, int $customerId, float $subtotal, int $restaurantId): array
    {
        $result = self::validate($voucherId, $subtotal, $restaurantId, $customerId);
        if (!$result['ok']) {
            return $result;
        }

        $_SESSION[self::SESSION_KEY] = $voucherId;

        return $result;
    }

    /** The discount from the customer's currently selected voucher. */
    public static function selectedDiscount(int $customerId, float $subtotal, int $restaurantId): float
    {
        $voucherId = (int) ($_SESSION[self::SESSION_KEY] ?? 0);
        if ($voucherId <= 0) {
            return 0.0;
        }

        $result = self::validate($voucherId, $subtotal, $restaurantId, $customerId);
        return $result['ok'] ? $result['discount'] : 0.0;
    }

    /** The selected voucher row (for display), or null. */
    public static function selected(int $customerId): ?array
    {
        $voucherId = (int) ($_SESSION[self::SESSION_KEY] ?? 0);
        return $voucherId > 0 ? Voucher::find($voucherId) : null;
    }

    /** Clear the selection (checkout consumed it, or cart changed). */
    public static function clearSelection(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }
}
