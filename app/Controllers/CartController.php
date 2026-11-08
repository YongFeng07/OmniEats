<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\CartService;

/**
 * Shopping cart (Module 5) — customer only.
 *
 * The update/remove/promo endpoints return JSON when requested with
 * fetch() (X-Requested-With: fetch), so the Cart page updates without
 * a page reload; plain form posts still work the old way.
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
            'discount'   => CartService::promoDiscount(),
            'promoCode'  => CartService::PROMO_CODE,
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

    /**
     * Update the quantity of one cart line.
     * AJAX (fetch) requests get a JSON payload instead of a redirect.
     */
    public function update(): void
    {
        $foodId = (int) ($_POST['food_item_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 1);
        $customerId = $this->customerId();

        $ok = CartService::updateQuantity($customerId, $foodId, $quantity);
        $contents = CartService::contents($customerId);

        if ($this->wantsJson()) {
            $this->json([
                'ok'       => $ok,
                'quantity' => max(0, $quantity),
                'line_subtotal' => number_format($this->lineSubtotal($contents, $foodId), 2),
                'subtotal' => number_format($contents['subtotal'], 2),
                'count'    => $contents['count'],
                'discount' => number_format(CartService::promoDiscount(), 2),
                'total'    => number_format(max(0, $contents['subtotal'] - CartService::promoDiscount()), 2),
            ]);
        }

        redirect('/cart');
    }

    /** Remove one cart line (AJAX-aware). */
    public function remove(): void
    {
        $foodId = (int) ($_POST['food_item_id'] ?? 0);
        $customerId = $this->customerId();

        $ok = CartService::removeItem($customerId, $foodId);
        $contents = CartService::contents($customerId);

        if ($this->wantsJson()) {
            $this->json([
                'ok'       => $ok,
                'removed'  => true,
                'subtotal' => number_format($contents['subtotal'], 2),
                'count'    => $contents['count'],
                'discount' => number_format(CartService::promoDiscount(), 2),
                'total'    => number_format(max(0, $contents['subtotal'] - CartService::promoDiscount()), 2),
            ]);
        }

        flash('success', 'Item removed from your cart.');
        redirect('/cart');
    }

    /** Save a per-item note (e.g. "no chilli") — AJAX endpoint. */
    public function note(): void
    {
        $foodId = (int) ($_POST['food_item_id'] ?? 0);
        $note = trim((string) ($_POST['note'] ?? ''));

        $ok = CartService::setNote($this->customerId(), $foodId, $note);

        if ($this->wantsJson()) {
            $this->json(['ok' => $ok, 'note' => $note]);
        }

        $ok
            ? flash('success', 'Note saved for this item.')
            : flash('error', 'Could not save the note.');
        redirect('/cart');
    }

    /** Apply (or reject) the demo promo code — AJAX endpoint. */
    public function promo(): void
    {
        $code = trim((string) ($_POST['code'] ?? ''));
        $customerId = $this->customerId();

        if (!CartService::applyPromo($code)) {
            $this->json([
                'ok'      => false,
                'message' => 'Invalid promo code. Try ' . CartService::PROMO_CODE . '.',
                'discount'=> '0.00',
            ]);
        }

        $contents = CartService::contents($customerId);
        $this->json([
            'ok'       => true,
            'message'  => 'Promo applied — RM ' . number_format(CartService::PROMO_VALUE, 2) . ' off!',
            'discount' => number_format(CartService::promoDiscount(), 2),
            'subtotal' => number_format($contents['subtotal'], 2),
            'total'    => number_format(max(0, $contents['subtotal'] - CartService::promoDiscount()), 2),
        ]);
    }

    public function clear(): void
    {
        CartService::clear($this->customerId());
        CartService::clearPromo();

        flash('success', 'Your cart has been cleared.');
        redirect('/cart');
    }

    /** Line subtotal for one item after an update. */
    private function lineSubtotal(array $contents, int $foodId): float
    {
        foreach ($contents['items'] as $line) {
            if ((int) $line['food_item_id'] === $foodId) {
                return (float) $line['unit_price'] * (int) $line['quantity'];
            }
        }
        return 0.0;
    }

    /** Was this request made with fetch()? */
    private function wantsJson(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch'
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    /** Send a JSON response and stop. */
    private function json(array $payload): never
    {
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }
}
