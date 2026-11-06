<?php

namespace App\Controllers;

use App\Models\Branch;
use App\Services\AuthService;
use App\Services\CartService;
use App\Services\OrderService;

/**
 * Checkout (Module 5): fulfilment selection (delivery / self-pickup)
 * and order submission. Payment handling arrives with Module 6 — the
 * new order starts in pending_payment.
 */
class CheckoutController
{
    private function customerId(): int
    {
        return (int) AuthService::user()['id'];
    }

    public function show(): void
    {
        $contents = CartService::contents($this->customerId());

        if ($contents['cart'] === null || $contents['items'] === []) {
            flash('error', 'Your cart is empty — add some food first.');
            redirect('/restaurants');
        }

        view('checkout/index', [
            'title'      => 'Checkout',
            'contents'   => $contents,
            'deliveryFee'=> CartService::DELIVERY_FEE,
            'discount'   => CartService::promoDiscount(),
            'promoCode'  => CartService::PROMO_CODE,
            'branches'   => Branch::allForRestaurant((int) $contents['restaurant']['id']),
        ]);
    }

    public function submit(): void
    {
        $data = [
            'fulfilment_type'  => (string) ($_POST['fulfilment_type'] ?? ''),
            'delivery_address' => (string) ($_POST['delivery_address'] ?? ''),
            'delivery_phone'   => (string) ($_POST['delivery_phone'] ?? ''),
            'branch_id'        => (string) ($_POST['branch_id'] ?? ''),
            'note'             => (string) ($_POST['note'] ?? ''),
        ];

        $orderId = OrderService::checkout($this->customerId(), $data);

        if ($orderId === null) {
            redirect('/checkout');
        }

        flash('success', 'Order placed! Complete the payment to confirm it.');
        redirect('/orders/' . $orderId);
    }
}
