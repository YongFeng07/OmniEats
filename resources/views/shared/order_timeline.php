<?php
/**
 * ★ Order status timeline (Module 5 unique feature). Renders the
 * visual progress bar from the database-backed order_status_history.
 * Expects: $order (with status), $history.
 */
$steps = ['pending_payment' => 'Pending payment', 'confirmed' => 'Confirmed',
          'preparing' => 'Preparing', 'ready' => 'Ready', 'completed' => 'Completed'];
$currentIndex = array_search($order['status'], array_keys($steps), true);
$times = [];
foreach ($history as $entry) {
    $times[$entry['to_status']] = $entry['created_at'];
}

// Cancelled orders show a red banner instead of a progress bar.
if ($order['status'] === 'cancelled'): ?>
    <div class="alert alert-danger mb-0">
        <strong>Order cancelled.</strong>
        <?php if (isset($times['cancelled'])): ?>
            Cancelled on <?= e(date('d M Y, H:i', strtotime($times['cancelled']))) ?>.
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="omnieats-timeline">
        <?php foreach ($steps as $status => $label): $i = array_search($status, array_keys($steps), true); ?>
            <div class="timeline-step <?= $i < $currentIndex ? 'done' : ($i === $currentIndex ? 'current' : '') ?>">
                <div class="timeline-dot"><?= $i < $currentIndex ? '✓' : ($i + 1) ?></div>
                <div class="timeline-label"><?= e($label) ?></div>
                <div class="timeline-time">
                    <?= isset($times[$status]) ? e(date('H:i', strtotime($times[$status]))) : '' ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
