<?php
/**
 * ★ Order status timeline (Cream Soft UI) — modern icon progress bar.
 * Icons: 💳 payment → ✅ confirmed → 🍳 preparing → 🛵 ready → ⭐ completed.
 * Completed steps turn soft green; the current step glows terracotta.
 * Data contract unchanged: $order (with status), $history.
 */
$steps = [
    'pending_payment' => ['label' => 'Payment',    'icon' => '💳'],
    'confirmed'       => ['label' => 'Confirmed',  'icon' => '✅'],
    'preparing'       => ['label' => 'Preparing',  'icon' => '🍳'],
    'ready'           => ['label' => 'Ready',      'icon' => '🛵'],
    'completed'       => ['label' => 'Completed',  'icon' => '⭐'],
];
$stepKeys = array_keys($steps);
$currentIndex = array_search($order['status'], $stepKeys, true);
$times = [];
foreach ($history as $entry) {
    $times[$entry['to_status']] = $entry['created_at'];
}

// Cancelled orders show a soft red banner instead of a progress bar.
if ($order['status'] === 'cancelled'): ?>
    <div class="alert alert-danger mb-0">
        <strong>Order cancelled.</strong>
        <?php if (isset($times['cancelled'])): ?>
            Cancelled on <?= e(date('d M Y, H:i', strtotime($times['cancelled']))) ?>.
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="cream-timeline">
        <?php foreach ($steps as $status => $step): $i = array_search($status, $stepKeys, true); ?>
            <div class="cream-step <?= $i < $currentIndex ? 'done' : ($i === $currentIndex ? 'current' : '') ?>">
                <div class="cream-step-icon"><?= e($step['icon']) ?></div>
                <div class="cream-step-label"><?= e($step['label']) ?></div>
                <div class="cream-step-time">
                    <?= isset($times[$status]) ? e(date('H:i', strtotime($times[$status]))) : '' ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
