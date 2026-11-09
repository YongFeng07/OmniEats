<?php
/**
 * ★ Order status timeline (unique feature — Wong Yong Feng).
 *
 * Merges the ORDER status history with the DELIVERY (fulfilment)
 * timestamps so delivery orders show the full Grab-style journey:
 *   💳 Payment → ✅ Confirmed → 🍳 Preparing → 🎒 Ready for Pickup
 *   → 🛵 Picked Up → 🛵 Out for Delivery → ⭐ Delivered  (🚫 Cancelled)
 * Pickup orders end at ⭐ Completed after Ready for Pickup.
 * Every timestamp is real database data (order_status_history +
 * fulfilments.picked_up_at / out_for_delivery_at / delivered_at).
 *
 * Expects: $order, $history, $fulfilment (may be null).
 */
$times = [];
foreach ($history as $entry) {
    $times[$entry['to_status']] = $entry['created_at'];
}

$isDelivery = $order['fulfilment_type'] === 'delivery';
$isCancelled = $order['status'] === 'cancelled';

if ($isCancelled): ?>
    <div class="alert alert-danger mb-0">
        <strong>Order cancelled.</strong>
        <?php if (isset($times['cancelled'])): ?>
            Cancelled on <?= e(date('d M Y, H:i', strtotime($times['cancelled']))) ?>.
        <?php endif; ?>
    </div>
<?php else:
    // The step list depends on the fulfilment type: delivery orders
    // include the rider journey; pickup orders stop at collection.
    $steps = [
        'payment'    => ['label' => 'Payment',           'icon' => '💳', 'time' => $times['pending_payment'] ?? null],
        'confirmed'  => ['label' => 'Confirmed',         'icon' => '✅', 'time' => $times['confirmed'] ?? null],
        'preparing'  => ['label' => 'Preparing',         'icon' => '🍳', 'time' => $times['preparing'] ?? null],
        'ready'      => ['label' => 'Ready for Pickup',  'icon' => '🎒', 'time' => $times['ready'] ?? null],
    ];

    if ($isDelivery && $fulfilment !== null) {
        $steps['picked_up']        = ['label' => 'Picked Up',        'icon' => '🍱', 'time' => $fulfilment['picked_up_at'] ?? null];
        $steps['out_for_delivery'] = ['label' => 'Out for Delivery', 'icon' => '🛵', 'time' => $fulfilment['out_for_delivery_at'] ?? null];
        $steps['delivered']        = ['label' => 'Delivered',        'icon' => '⭐', 'time' => $fulfilment['delivered_at'] ?? null];
    } else {
        $steps['completed'] = ['label' => 'Completed', 'icon' => '⭐', 'time' => $times['completed'] ?? null];
    }

    // The CURRENT step: the last step that actually has a timestamp,
    // plus the following step is highlighted as in-progress.
    $stepList = array_keys($steps);
    $reached = [];
    foreach ($stepList as $key) {
        if ($steps[$key]['time'] !== null) {
            $reached[] = $key;
        }
    }
    $currentKey = $reached !== [] ? $reached[count($reached) - 1] : 'payment';
    $nextIndex = array_search($currentKey, $stepList, true) + 1;
    $currentIndex = $order['status'] === 'completed' || ($isDelivery && $fulfilment !== null && $fulfilment['status'] === 'delivered')
        ? count($stepList) - 1
        : min($nextIndex, count($stepList) - 1);
    ?>
    <div class="cream-timeline">
        <?php $i = 0; foreach ($steps as $key => $step): ?>
            <div class="cream-step <?= $step['time'] !== null ? 'done' : ($i === $currentIndex ? 'current' : '') ?>">
                <div class="cream-step-icon"><?= e($step['icon']) ?></div>
                <div class="cream-step-label"><?= e($step['label']) ?></div>
                <div class="cream-step-time">
                    <?= $step['time'] !== null ? e(date('H:i', strtotime($step['time']))) : '' ?>
                </div>
            </div>
        <?php $i++; endforeach; ?>
    </div>
<?php endif; ?>
