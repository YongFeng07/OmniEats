<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\FoodItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;

/**
 * Shopping cart business logic (Module 5).
 *
 * Business rules enforced here:
 *  - one restaurant per cart (adding from a second restaurant is
 *    blocked with a clear message until the cart is cleared);
 *  - only available items of an OPEN, approved restaurant can be added;
 *  - totals are always computed here, never trusted from the browser.
 */
class CartService
{
    public const DELIVERY_FEE = 5.00;

    /**
     * Vouchers (Promotion & Voucher module) replaced the old hardcoded
     * demo promo. The selection lives in the session; every discount
     * goes through VoucherService's smart validation.
     */
    public static function voucherDiscount(int $customerId, float $subtotal, int $restaurantId): float
    {
        return VoucherService::selectedDiscount($customerId, $subtotal, $restaurantId);
    }

    /** The voucher currently selected for the cart, or null. */
    public static function voucherSelected(int $customerId): ?array
    {
        return VoucherService::selected($customerId);
    }

    public static function addItem(int $customerId, int $foodItemId, int $quantity, bool $silent = false): bool
    {
        if ($quantity < 1) {
            if (!$silent) {
                flash('error', 'Quantity must be at least 1.');
            }
            return false;
        }

        $food = FoodItem::find($foodItemId);
        if ($food === null) {
            if (!$silent) {
                flash('error', 'Food item not found.');
            }
            return false;
        }

        $restaurant = Restaurant::find((int) $food['restaurant_id']);
        if ($restaurant === null || $restaurant['status'] !== 'approved') {
            if (!$silent) {
                flash('error', 'This restaurant is not available.');
            }
            return false;
        }

        // Availability control (M2): closed restaurants take no orders.
        if ((int) $restaurant['is_open'] !== 1) {
            if (!$silent) {
                flash('error', 'This restaurant is currently CLOSED and not accepting orders.');
            }
            return false;
        }

        if ((int) $food['is_available'] !== 1) {
            if (!$silent) {
                flash('error', 'This item is currently unavailable.');
            }
            return false;
        }

        $cart = Cart::findForCustomer($customerId);

        // Single-restaurant rule: a cart holding items from another
        // restaurant cannot be mixed.
        if ($cart !== null
            && (int) $cart['restaurant_id'] !== (int) $food['restaurant_id']
            && CartItem::countForCart((int) $cart['id']) > 0
        ) {
            $other = Restaurant::find((int) $cart['restaurant_id']);
            if (!$silent) {
                flash('error', 'Your cart already contains items from ' . ($other['name'] ?? 'another restaurant')
                    . '. Clear your cart before ordering from a different restaurant.');
            }
            return false;
        }

        if ($cart === null) {
            $cartId = Cart::create($customerId, (int) $food['restaurant_id']);
        } else {
            $cartId = (int) $cart['id'];
            if ((int) $cart['restaurant_id'] !== (int) $food['restaurant_id']) {
                // Empty cart from another restaurant — switch to this one.
                Cart::setRestaurant($cartId, (int) $food['restaurant_id']);
            }
        }

        CartItem::addOrIncrement($cartId, $foodItemId, $quantity);

        return true;
    }

    /**
     * "Order again" (Grab-style): re-add the items of a past order to
     * the cart. Every addItem rule still applies (single restaurant,
     * OPEN, available items). Returns the number of lines re-added and
     * flashes one aggregated result message.
     */
    public static function addFromOrder(int $customerId, int $orderId): int
    {
        $order = Order::find($orderId);
        if ($order === null || (int) $order['customer_id'] !== $customerId) {
            flash('error', 'Order not found.');
            return 0;
        }

        $lines = OrderItem::allForOrder($orderId);
        $added = 0;
        foreach ($lines as $line) {
            if (self::addItem($customerId, (int) $line['food_item_id'], (int) $line['quantity'], true)) {
                $added++;
            }
        }

        if ($added === 0) {
            flash('error', 'Could not re-order: this restaurant is CLOSED or its items are no longer available.');
        } elseif ($added < count($lines)) {
            flash('success', "Re-ordered {$added} item(s) — " . (count($lines) - $added) . ' item(s) are no longer available and were skipped.');
        } else {
            flash('success', "Re-ordered all {$added} item(s) — review your cart to check out.");
        }

        return $added;
    }

    /**
     * Per-item note (e.g. "no chilli") — 120 characters max.
     * Returns false when the line does not belong to the customer's cart.
     */
    public static function setNote(int $customerId, int $foodItemId, string $note): bool
    {
        if (mb_strlen($note) > 120) {
            flash('error', 'The note must not exceed 120 characters.');
            return false;
        }

        $cart = Cart::findForCustomer($customerId);
        if ($cart === null) {
            flash('error', 'Your cart is empty.');
            return false;
        }

        CartItem::setNote((int) $cart['id'], $foodItemId, $note);
        return true;
    }

    public static function updateQuantity(int $customerId, int $foodItemId, int $quantity): bool
    {        if ($quantity < 1) {
            return self::removeItem($customerId, $foodItemId);
        }

        $cart = Cart::findForCustomer($customerId);
        if ($cart === null) {
            flash('error', 'Your cart is empty.');
            return false;
        }

        CartItem::updateQuantity((int) $cart['id'], $foodItemId, $quantity);

        return true;
    }

    public static function removeItem(int $customerId, int $foodItemId): bool
    {
        $cart = Cart::findForCustomer($customerId);
        if ($cart === null) {
            flash('error', 'Your cart is empty.');
            return false;
        }

        CartItem::remove((int) $cart['id'], $foodItemId);

        return true;
    }

    public static function clear(int $customerId): bool
    {
        $cart = Cart::findForCustomer($customerId);
        if ($cart !== null) {
            Cart::delete((int) $cart['id']);
        }

        return true;
    }

    /**
     * Cart contents for the logged-in customer: cart, restaurant, item
     * lines and the backend-calculated subtotal.
     */
    public static function contents(int $customerId): array
    {
        $cart = Cart::findForCustomer($customerId);
        if ($cart === null) {
            return ['cart' => null, 'restaurant' => null, 'items' => [], 'subtotal' => 0.0, 'count' => 0];
        }

        $items = CartItem::allForCart((int) $cart['id']);
        $restaurant = Restaurant::find((int) $cart['restaurant_id']);

        $subtotal = 0.0;
        $count = 0;
        foreach ($items as $item) {
            $subtotal += (float) $item['unit_price'] * (int) $item['quantity'];
            $count += (int) $item['quantity'];
        }

        return [
            'cart'       => $cart,
            'restaurant' => $restaurant,
            'items'      => $items,
            'subtotal'   => $subtotal,
            'count'      => $count,
        ];
    }

    /** Number of items in the customer's cart (nav badge). */
    public static function cartCount(int $customerId): int
    {
        $cart = Cart::findForCustomer($customerId);
        return $cart !== null ? CartItem::countForCart((int) $cart['id']) : 0;
    }
}
