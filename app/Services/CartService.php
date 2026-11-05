<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\FoodItem;
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

    public static function addItem(int $customerId, int $foodItemId, int $quantity): bool
    {
        if ($quantity < 1) {
            flash('error', 'Quantity must be at least 1.');
            return false;
        }

        $food = FoodItem::find($foodItemId);
        if ($food === null) {
            flash('error', 'Food item not found.');
            return false;
        }

        $restaurant = Restaurant::find((int) $food['restaurant_id']);
        if ($restaurant === null || $restaurant['status'] !== 'approved') {
            flash('error', 'This restaurant is not available.');
            return false;
        }

        // Availability control (M2): closed restaurants take no orders.
        if ((int) $restaurant['is_open'] !== 1) {
            flash('error', 'This restaurant is currently CLOSED and not accepting orders.');
            return false;
        }

        if ((int) $food['is_available'] !== 1) {
            flash('error', 'This item is currently unavailable.');
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
            flash('error', 'Your cart already contains items from ' . ($other['name'] ?? 'another restaurant')
                . '. Clear your cart before ordering from a different restaurant.');
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

    public static function updateQuantity(int $customerId, int $foodItemId, int $quantity): bool
    {
        if ($quantity < 1) {
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
