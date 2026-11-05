<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\CartService;

/**
 * Shopping cart (Module 5) — customer only. All actions derive the
 * cart from the session; food items carry their own restaurant.
 */
class CartController
{
    private function customerId(): int
    {
        return (int) AuthService::user()['id'];
    }

    public function show(): void
    {
        $contents = CartService::contents($this->customerId());

        view('cart/index', [
            'title'      => 'My Cart',
            'contents'   => $contents,
            'deliveryFee'=> CartService::DELIVERY_FEE,
        ]);
    }

    /** Add an item (or increase its quantity). */
    public function add(): void
    {
        $foodId = (int) ($_POST['food_item_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 1);

        if (!CartService::addItem($this->customerId(), $foodId, $quantity)) {
            // Send the customer back to the restaurant page.
            $food = \App\Models\FoodItem::find($foodId);
            redirect($food !== null ? '/restaurants/' . (int) $food['restaurant_id'] : '/restaurants');
        }

        flash('success', 'Added to your cart.');
        redirect('/cart');
    }

    /** Update the quantity of one cart line. */
    public function update(): void
    {
        $foodId = (int) ($_POST['food_item_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 1);

        CartService::updateQuantity($this->customerId(), $foodId, $quantity);

        redirect('/cart');
    }

    public function remove(): void
    {
        $foodId = (int) ($_POST['food_item_id'] ?? 0);

        CartService::removeItem($this->customerId(), $foodId);

        flash('success', 'Item removed from your cart.');
        redirect('/cart');
    }

    public function clear(): void
    {
        CartService::clear($this->customerId());

        flash('success', 'Your cart has been cleared.');
        redirect('/cart');
    }
}
