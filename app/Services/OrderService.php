<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\FoodItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;

/**
 * Order business logic (Module 5) including the unique feature:
 * ★ LIVE ORDER STATUS TRACKING — every status change is validated
 * against a transition map and written to order_status_history, which
 * drives the customer-facing timeline (JS polling reads the current
 * status from /orders/{id}/status).
 */
class OrderService
{
    /**
     * Valid transitions between order statuses. Completed and cancelled
     * are terminal — customers cannot modify completed orders.
     */
    public const TRANSITIONS = [
        'pending_payment' => ['confirmed', 'cancelled'],
        'confirmed'       => ['preparing', 'cancelled'],
        'preparing'       => ['ready', 'cancelled'],
        'ready'           => ['completed', 'cancelled'],
        'completed'       => [],
        'cancelled'       => [],
    ];

    /**
     * Checkout: creates the order (+ snapshot items, + first history
     * row) and clears the cart — all in one transaction. Every rule is
     * re-checked here: empty cart, approved + OPEN restaurant,
     * available items, valid fulfilment, backend-computed totals.
     * Returns the new order id, or null after flashing the error.
     */
    public static function checkout(int $customerId, array $data): ?int
    {
        $contents = CartService::contents($customerId);

        if ($contents['cart'] === null || $contents['items'] === []) {
            flash('error', 'Your cart is empty.');
            return null;
        }

        $restaurant = $contents['restaurant'];
        if ($restaurant === null || $restaurant['status'] !== 'approved') {
            flash('error', 'This restaurant is not available.');
            return null;
        }

        // Availability control (M2): re-checked during checkout.
        if ((int) $restaurant['is_open'] !== 1) {
            flash('error', $restaurant['name'] . ' is currently CLOSED and not accepting orders.');
            return null;
        }

        // Availability of every item is re-checked during checkout (M3).
        foreach ($contents['items'] as $line) {
            $food = FoodItem::find((int) $line['food_item_id']);
            if ($food === null || (int) $food['is_available'] !== 1) {
                flash('error', $line['food_name'] . ' is no longer available. Please remove it from your cart.');
                return null;
            }
        }

        $type = $data['fulfilment_type'] ?? '';
        if (!in_array($type, ['delivery', 'pickup'], true)) {
            flash('error', 'Please choose delivery or self-pickup.');
            return null;
        }

        $address = trim((string) ($data['delivery_address'] ?? ''));
        $phone = trim((string) ($data['delivery_phone'] ?? ''));

        if ($type === 'delivery') {
            if ($address === '' || $phone === '') {
                flash('error', 'Delivery address and phone are required for delivery orders.');
                return null;
            }
        } else {
            $address = null;
            $phone = null;
        }

        // Optional branch: when chosen it must belong to the restaurant.
        $branchId = (int) ($data['branch_id'] ?? 0);
        if ($branchId > 0) {
            $branch = \App\Models\Branch::find($branchId);
            if ($branch === null || (int) $branch['restaurant_id'] !== (int) $restaurant['id']) {
                flash('error', 'Please choose a valid branch.');
                return null;
            }
        } else {
            $branchId = null;
        }

        // Totals are computed HERE, never trusted from the browser.
        // Real business rule: a restaurant with free_delivery = 1 pays
        // no delivery fee on delivery orders (matches the card badge).
        // The demo promo code (applied in the cart) is deducted and
        // stored on the order; it is consumed by this checkout.
        $subtotal = $contents['subtotal'];
        $freeDelivery = $type === 'delivery' && (int) $restaurant['free_delivery'] === 1;
        $deliveryFee = $freeDelivery ? 0.00 : ($type === 'delivery' ? CartService::DELIVERY_FEE : 0.00);
        $discount = min(CartService::promoDiscount(), $subtotal + $deliveryFee);
        $total = $subtotal + $deliveryFee - $discount;

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $orderId = Order::create([
                'order_no'         => Order::nextOrderNo(),
                'customer_id'      => $customerId,
                'restaurant_id'    => (int) $restaurant['id'],
                'branch_id'        => $branchId,
                'fulfilment_type'  => $type,
                'delivery_address' => $address,
                'delivery_phone'   => $phone,
                'subtotal'         => $subtotal,
                'delivery_fee'     => $deliveryFee,
                'discount'         => $discount,
                'total'            => $total,
                'note'             => trim((string) ($data['note'] ?? '')),
            ]);

            // Historical snapshots: name and price at purchase time.
            foreach ($contents['items'] as $line) {
                OrderItem::create($orderId, [
                    'food_item_id' => (int) $line['food_item_id'],
                    'food_name'    => $line['food_name'],
                    'unit_price'   => $line['unit_price'],
                    'quantity'     => (int) $line['quantity'],
                    'subtotal'     => (float) $line['unit_price'] * (int) $line['quantity'],
                ]);
            }

            OrderStatusHistory::add($orderId, null, 'pending_payment', $customerId, 'Order placed');

            // The cart is consumed by the order; so is the promo code.
            Cart::delete((int) $contents['cart']['id']);
            CartService::clearPromo();

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            flash('error', 'The order could not be created. Please try again.');
            return null;
        }

        activity('place_order', 'order', $orderId);

        return $orderId;
    }

    /**
     * ★ Status transition with validation. The actor may be a customer
     * (cancel own order before preparation) or restaurant staff (their
     * own restaurant's orders). Invalid transitions are rejected.
     */
    public static function transition(int $orderId, string $newStatus, array $actor, string $note = ''): bool
    {
        $order = Order::find($orderId);
        if ($order === null) {
            flash('error', 'Order not found.');
            return false;
        }

        $actorRoles = $actor['roles'] ?? [];
        $isStaff = array_intersect(['restaurant_owner', 'restaurant_staff'], $actorRoles) !== [];
        $isCustomer = in_array('customer', $actorRoles, true);
        $isAdmin = in_array('super_admin', $actorRoles, true);

        // Tenant isolation: staff may only touch their own restaurant's
        // orders; customers only their own.
        if ($isStaff && !$isAdmin) {
            $tenant = (int) ($actor['restaurant_id'] ?? 0);
            if ($tenant !== (int) $order['restaurant_id']) {
                flash('error', 'Order not found.');
                return false;
            }
        } elseif ($isCustomer && !$isStaff && !$isAdmin) {
            if ((int) $actor['id'] !== (int) $order['customer_id']) {
                flash('error', 'Order not found.');
                return false;
            }
        }

        $current = $order['status'];

        // Invalid transition (e.g. skipping a step or moving backwards)?
        if (!in_array($newStatus, self::TRANSITIONS[$current] ?? [], true)) {
            flash('error', 'This status change is not allowed (from ' . $current . ' to ' . $newStatus . ').');
            return false;
        }

        // Customers may only CANCEL their own orders, and only while
        // the order has not started preparation.
        if ($isCustomer && !$isStaff && !$isAdmin) {
            if ($newStatus !== 'cancelled' || !in_array($current, ['pending_payment', 'confirmed'], true)) {
                flash('error', 'You can only cancel an order before the restaurant starts preparing it.');
                return false;
            }
        }

        Order::setStatus($orderId, $newStatus);
        OrderStatusHistory::add($orderId, $current, $newStatus, (int) $actor['id'], $note !== '' ? $note : null);

        // M6 integration: a cancelled order cancels its fulfilment.
        if ($newStatus === 'cancelled') {
            FulfilmentService::cancelForOrder($orderId);
        }

        activity('order_' . $newStatus, 'order', $orderId);

        return true;
    }

    /**
     * System-driven transition (e.g. payment success confirms an
     * order). The transition map is still validated; the history row
     * records a NULL actor meaning "system".
     */
    public static function transitionBySystem(int $orderId, string $newStatus, string $note = ''): bool
    {
        $order = Order::find($orderId);
        if ($order === null) {
            flash('error', 'Order not found.');
            return false;
        }

        $current = $order['status'];

        if (!in_array($newStatus, self::TRANSITIONS[$current] ?? [], true)) {
            flash('error', 'This status change is not allowed (from ' . $current . ' to ' . $newStatus . ').');
            return false;
        }

        Order::setStatus($orderId, $newStatus);
        OrderStatusHistory::add($orderId, $current, $newStatus, null, $note !== '' ? $note : null);

        if ($newStatus === 'cancelled') {
            FulfilmentService::cancelForOrder($orderId);
        }

        activity('order_' . $newStatus, 'order', $orderId);

        return true;
    }
}
