<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\CartService;
use App\Services\VoucherService;

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

        $customerId = $this->customerId();
        $voucher = CartService::voucherSelected($customerId);

        view('cart/index', [
            'title'       => 'My Cart',
            'contents'    => $contents,
            'deliveryFee' => CartService::DELIVERY_FEE,
            'discount'    => $this->voucherDiscountFor($contents),
            'voucher'     => $voucher,
            'voucherTray' => $contents['restaurant'] !== null
                ? VoucherService::trayFor((int) $contents['restaurant']['id'], $contents['subtotal'])
                : [],
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
        $discount = $this->voucherDiscountFor($contents);

        if ($this->wantsJson()) {
            $this->json([
                'ok'       => $ok,
                'quantity' => max(0, $quantity),
                'line_subtotal' => number_format($this->lineSubtotal($contents, $foodId), 2),
                'subtotal' => number_format($contents['subtotal'], 2),
                'count'    => $contents['count'],
                'discount' => number_format($discount, 2),
                'total'    => number_format(max(0, $contents['subtotal'] - $discount), 2),
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
        $discount = $this->voucherDiscountFor($contents);

        if ($this->wantsJson()) {
            $this->json([
                'ok'       => $ok,
                'removed'  => true,
                'subtotal' => number_format($contents['subtotal'], 2),
                'count'    => $contents['count'],
                'discount' => number_format($discount, 2),
                'total'    => number_format(max(0, $contents['subtotal'] - $discount), 2),
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

    /**
     * Apply (or reject) a voucher by id — AJAX endpoint, Grab-style
     * voucher tray: the customer taps a voucher and the server runs
     * the smart validation (VoucherService).
     */
    public function voucher(): void
    {
        $voucherId = (int) ($_POST['voucher_id'] ?? 0);
        $customerId = $this->customerId();
        $contents = CartService::contents($customerId);

        if ($contents['restaurant'] === null) {
            $this->json(['ok' => false, 'message' => 'Your cart is empty.', 'discount' => '0.00']);
        }

        $result = VoucherService::apply(
            $voucherId,
            $customerId,
            $contents['subtotal'],
            (int) $contents['restaurant']['id']
        );

        if ($this->wantsJson()) {
            $this->json([
                'ok'       => $result['ok'],
                'message'  => $result['ok']
                    ? 'Voucher applied — RM ' . number_format($result['discount'], 2) . ' off!'
                    : $result['message'],
                'discount' => number_format($result['ok'] ? $result['discount'] : 0.0, 2),
                'subtotal' => number_format($contents['subtotal'], 2),
                'total'    => number_format(max(0, $contents['subtotal'] - ($result['ok'] ? $result['discount'] : 0.0)), 2),
            ]);
        }

        if ($result['ok']) {
            flash('success', 'Voucher applied.');
        } else {
            flash('error', $result['message']);
        }
        redirect('/cart');
    }

    public function clear(): void
    {
        CartService::clear($this->customerId());
        VoucherService::clearSelection();

        flash('success', 'Your cart has been cleared.');
        redirect('/cart');
    }

    /** Voucher discount for the current cart (0 when none/invalid). */
    private function voucherDiscountFor(array $contents): float
    {
        if ($contents['restaurant'] === null) {
            return 0.0;
        }
        return CartService::voucherDiscount(
            $this->customerId(),
            $contents['subtotal'],
            (int) $contents['restaurant']['id']
        );
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
