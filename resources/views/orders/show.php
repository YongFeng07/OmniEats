<?php
/**
 * Customer order detail (Module 5): tracking hero with a large status
 * message, ★ live icon timeline with JS polling, payment section,
 * fulfilment details and a rider card (delivery orders).
 */
$statusBadge = 'badge_class'; // shared helper in app/Helpers/functions.php

$statusHero = match ($order['status']) {
    'pending_payment' => ['icon' => '💳', 'message' => 'Waiting for your payment'],
    'confirmed'       => ['icon' => '📋', 'message' => 'Merchant received your order'],
    'preparing'       => ['icon' => '🍳', 'message' => 'Merchant is preparing your food'],
    'ready'           => $order['fulfilment_type'] === 'delivery'
                          ? ['icon' => '🛵', 'message' => 'Your rider is on the way!']
                          : ['icon' => '🎒', 'message' => 'Ready for collection!'],
    'completed'       => ['icon' => '⭐', 'message' => 'Order completed — enjoy your meal!'],
    'cancelled'       => ['icon' => '🚫', 'message' => 'Order cancelled'],
    default           => ['icon' => '🍽️', 'message' => 'Processing your order'],
};
$showRider = $order['fulfilment_type'] === 'delivery'
    && in_array($order['status'], ['ready', 'completed'], true);

// Estimated ready/arrival time (real data: the restaurant's
// delivery_minutes profile value + payment time). Clearly an estimate.
$eta = null;
if ($restaurant !== null && $restaurant['delivery_minutes'] !== null
    && in_array($order['status'], ['confirmed', 'preparing', 'ready'], true)) {
    $base = ($payment !== null && !empty($payment['paid_at']))
        ? strtotime($payment['paid_at'])
        : strtotime($order['created_at']);
    $eta = date('H:i', $base + (int) $restaurant['delivery_minutes'] * 60);
}
$isTerminal = in_array($order['status'], ['completed', 'cancelled'], true);
?>
<div class="container py-4" style="max-width: 960px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h1 class="h4 mb-0">Order <code><?= e($order['order_no']) ?></code></h1>
        <span class="<?= $statusBadge($order['status']) ?>">
            <?= e(ucwords(str_replace('_', ' ', $order['status']))) ?>
        </span>
    </div>

    <div class="tracking-hero mb-4">
        <div class="tracking-hero-icon"><?= e($statusHero['icon']) ?></div>
        <div class="flex-grow-1">
            <h2 class="h4 mb-1"><?= e($statusHero['message']) ?></h2>
            <p class="text-muted small mb-0">
                Order <code><?= e($order['order_no']) ?></code> ·
                <span class="<?= $statusBadge($order['status']) ?>">
                    <?= e(ucwords(str_replace('_', ' ', $order['status']))) ?>
                </span>
            </p>
            <?php if ($eta !== null): ?>
                <p class="small mb-0 mt-2">
                    <strong>
                        <?= $order['fulfilment_type'] === 'delivery' ? '🛵 Estimated arrival' : '🎒 Estimated ready' ?>
                        ~<?= e($eta) ?>
                    </strong>
                    <span class="text-muted">(estimate based on the restaurant's preparation time)</span>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($isTerminal): ?>
        <div class="d-flex flex-wrap gap-2 mb-4">
            <form method="post" action="<?= url('/orders/' . (int) $order['id'] . '/reorder') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-primary">↻ Order again</button>
            </form>
            <form method="post" action="<?= url('/orders/' . (int) $order['id'] . '/archive') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-secondary">
                    <?= (int) $order['is_archived'] === 1 ? 'Restore to My Orders' : 'Archive order' ?>
                </button>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($showRider): ?>
        <?php if ($fulfilment !== null && !empty($fulfilment['rider_id'])): ?>
            <?php $riderUser = \App\Models\User::find((int) $fulfilment['rider_id']); ?>
            <?php $riderProfile = $riderUser !== null ? \App\Models\RiderProfile::findForUser((int) $riderUser['id']) : null; ?>
            <div class="rider-card mb-4">
                <div class="rider-avatar">🧑‍✈️</div>
                <div class="flex-grow-1">
                    <p class="fw-bold mb-0 text-dark"><?= e($riderUser['name'] ?? 'Your rider') ?></p>
                    <p class="small text-muted mb-0">
                        <?= $riderProfile !== null && !empty($riderProfile['vehicle_plate'])
                            ? 'Vehicle: ' . e($riderProfile['vehicle_plate']) . ' · ' : '' ?>
                        <?= e($riderProfile['vehicle_type'] ?? 'Bike') ?>
                    </p>
                </div>
                <a class="btn btn-outline-primary btn-sm"
                   href="tel:<?= e($riderUser['phone'] ?? '') ?>">📞 Call rider</a>
            </div>
        <?php else: ?>
            <div class="rider-card mb-4" style="opacity: .75;">
                <div class="rider-avatar">🛵</div>
                <div class="flex-grow-1">
                    <p class="fw-bold mb-0 text-dark">Rider is being assigned</p>
                    <p class="small text-muted mb-0">The platform administrator will assign a rider shortly.</p>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h2 class="h6 text-muted text-uppercase mb-3">Live status</h2>
            <?php view('shared/order_timeline', ['order' => $order, 'history' => $history], null); ?>
            <p class="text-muted small mt-3 mb-0">
                Updates automatically every few seconds — no refresh needed.
            </p>
        </div>
    </div>

    <?php if ($order['status'] === 'pending_payment'): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h6 text-muted text-uppercase mb-1">Payment</h2>
                <p class="text-muted small mb-3">
                    Amount due: <strong>RM <?= e(number_format((float) $order['total'], 2)) ?></strong>
                    — payments are simulated for this academic project. No real charges apply.
                </p>

                <?php if ($payment !== null && $payment['status'] === 'failed'): ?>
                    <div class="alert alert-danger small">Your last payment attempt failed (simulated). Please try again.</div>
                <?php endif; ?>

                <form method="post" action="<?= url('/orders/' . (int) $order['id'] . '/pay') ?>">
                    <?= csrf_field() ?>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" id="method_online" name="method"
                               value="online" checked onchange="syncDecline()">
                        <label class="form-check-label" for="method_online">Online payment (simulated gateway)</label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" id="method_cash" name="method"
                               value="cash" onchange="syncDecline()">
                        <label class="form-check-label" for="method_cash">Cash (pay on pickup / delivery)</label>
                    </div>
                    <div class="form-check mb-3" id="decline-row">
                        <input class="form-check-input" type="checkbox" id="simulate_decline"
                               name="simulate_decline" value="1">
                        <label class="form-check-label" for="simulate_decline">
                            Simulate card decline <span class="text-muted small">(demo control — makes the gateway return a failure)</span>
                        </label>
                    </div>
                    <button type="submit" class="btn omnieats-btn">Pay RM <?= e(number_format((float) $order['total'], 2)) ?></button>
                    <?php if ($canCancel): ?>
                        <button type="submit" formaction="<?= url('/orders/' . (int) $order['id'] . '/cancel') ?>"
                                formmethod="post" class="btn btn-outline-danger"
                                onclick="return confirm('Cancel this order?');">Cancel order</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    <?php elseif ($payment !== null && $payment['status'] === 'success'): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h6 text-muted text-uppercase mb-3">Payment receipt</h2>
                <table class="table table-sm mb-0">
                    <tr><th class="ps-0 text-muted" style="width: 40%;">Status</th>
                        <td><span class="badge text-bg-success">Paid</span></td></tr>
                    <tr><th class="ps-0 text-muted">Method</th>
                        <td><?= e(ucfirst($payment['method'])) ?></td></tr>
                    <tr><th class="ps-0 text-muted">Amount</th>
                        <td>RM <?= e(number_format((float) $payment['amount'], 2)) ?></td></tr>
                    <tr><th class="ps-0 text-muted">Reference</th>
                        <td><code><?= e($payment['transaction_ref']) ?></code></td></tr>
                    <tr><th class="ps-0 text-muted">Paid at</th>
                        <td><?= e(date('d M Y, H:i', strtotime($payment['paid_at']))) ?></td></tr>
                </table>
            </div>
        </div>
    <?php elseif ($canCancel && $order['status'] === 'confirmed'): ?>
        <div class="mb-4">
            <form method="post" action="<?= url('/orders/' . (int) $order['id'] . '/cancel') ?>"
                  onsubmit="return confirm('Cancel this order?');">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-outline-danger">Cancel order</button>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($fulfilment !== null): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h6 text-muted text-uppercase mb-3">
                    <?= e(ucfirst($fulfilment['type'])) ?> details
                </h2>
                <p class="mb-1">
                    Status:
                    <span class="badge text-bg-secondary">
                        <?= e(ucwords(str_replace('_', ' ', $fulfilment['status']))) ?>
                    </span>
                </p>
                <?php if ($fulfilment['type'] === 'delivery'): ?>
                    <p class="small mb-1"><strong>Address:</strong> <?= e($fulfilment['address']) ?></p>
                    <p class="small mb-0"><strong>Phone:</strong> <?= e($fulfilment['phone']) ?></p>
                <?php endif; ?>
                <?php if (!empty($fulfilment['completed_at'])): ?>
                    <p class="small text-muted mb-0">
                        Completed at <?= e(date('d M Y, H:i', strtotime($fulfilment['completed_at']))) ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h2 class="h6 text-muted text-uppercase mb-3">Items</h2>
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Item</th><th>Unit price</th><th>Qty</th><th class="text-end">Subtotal</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $line): ?>
                                <tr>
                                    <td>
                                        <?= e($line['food_name']) ?>
                                        <?php if (!empty($line['note'])): ?>
                                            <br><small class="text-muted">📝 <?= e($line['note']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>RM <?= e(number_format((float) $line['unit_price'], 2)) ?></td>
                                    <td><?= (int) $line['quantity'] ?></td>
                                    <td class="text-end">RM <?= e(number_format((float) $line['subtotal'], 2)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <hr>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Subtotal</span><span>RM <?= e(number_format((float) $order['subtotal'], 2)) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Delivery fee</span><span>RM <?= e(number_format((float) $order['delivery_fee'], 2)) ?></span>
                    </div>
                    <?php if ((float) $order['discount'] > 0): ?>
                        <div class="d-flex justify-content-between mb-1 text-success">
                            <span>Voucher discount</span><span>− RM <?= e(number_format((float) $order['discount'], 2)) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Total</span><span>RM <?= e(number_format((float) $order['total'], 2)) ?></span>
                    </div>
                    <?php if (!empty($order['note'])): ?>
                        <p class="text-muted small mt-3 mb-0">Note: <?= e($order['note']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h2 class="h6 text-muted text-uppercase mb-3">Restaurant &amp; fulfilment</h2>
                    <p class="mb-1"><a class="text-decoration-none"
                        href="<?= url('/restaurants/' . (int) $restaurant['id']) ?>"><?= e($restaurant['name']) ?></a></p>
                    <p class="text-muted small mb-3">
                        <?= e(ucfirst($order['fulfilment_type'])) ?>
                        <?php if ($branch): ?> · <?= e($branch['name']) ?><?php endif; ?>
                    </p>
                    <?php if ($order['fulfilment_type'] === 'delivery'): ?>
                        <p class="small mb-1"><strong>Deliver to:</strong><br>
                            <?= e($order['delivery_address']) ?></p>
                        <p class="small mb-0"><strong>Phone:</strong> <?= e($order['delivery_phone']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h6 text-muted text-uppercase mb-3">Status history</h2>
                    <?php foreach (array_reverse($history) as $entry): ?>
                        <div class="d-flex justify-content-between small mb-2">
                            <span>
                                <span class="text-muted"><?= e($entry['from_status'] ?? '—') ?> →</span>
                                <strong><?= e(ucwords(str_replace('_', ' ', $entry['to_status']))) ?></strong>
                                <?php if (!empty($entry['note'])): ?><br>
                                    <span class="text-muted"><?= e($entry['note']) ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="text-muted text-nowrap"><?= e(date('d M H:i', strtotime($entry['created_at']))) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ★ Grab/Foodpanda-style rating: completed orders can be reviewed once. -->
    <?php if ($order['status'] === 'completed'): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <?php if ($review !== null): ?>
                    <h2 class="h6 text-muted text-uppercase mb-2">Your review</h2>
                    <p class="mb-1">
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                            <span class="fs-5"><?= $s <= (int) $review['rating'] ? '⭐' : '☆' ?></span>
                        <?php endfor; ?>
                    </p>
                    <?php if (!empty($review['comment'])): ?>
                        <p class="text-muted small mb-0">"<?= e($review['comment']) ?>"</p>
                    <?php endif; ?>
                <?php else: ?>
                    <h2 class="h6 text-muted text-uppercase mb-3">Rate your meal</h2>
                    <form method="post" action="<?= url('/orders/' . (int) $order['id'] . '/review') ?>">
                        <?= csrf_field() ?>
                        <div class="mb-3" id="star-row">
                            <label class="form-label mb-1">Your rating</label><br>
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <button type="button" class="btn btn-link p-0 fs-4 text-decoration-none star-btn"
                                        data-star="<?= $s ?>" aria-label="<?= $s ?> star(s)">☆</button>
                            <?php endfor; ?>
                            <input type="hidden" name="rating" id="rating" value="5" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="comment">Comment <span class="text-muted">(optional, 500 chars)</span></label>
                            <textarea class="form-control" id="comment" name="comment" rows="2" maxlength="500"></textarea>
                        </div>
                        <button type="submit" class="btn omnieats-btn btn-sm">Submit review</button>
                    </form>
                    <script>
                        // Star picker: highlight the chosen rating.
                        (() => {
                            const buttons = document.querySelectorAll('#star-row .star-btn');
                            const input = document.getElementById('rating');
                            const paint = n => buttons.forEach((b, i) => { b.textContent = i < n ? '⭐' : '☆'; });
                            buttons.forEach(b => b.addEventListener('click', () => {
                                input.value = b.dataset.star;
                                paint(Number(b.dataset.star));
                            }));
                            paint(5);
                        })();
                    </script>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    // The "simulate decline" demo control only applies to online payments.
    function syncDecline() {
        const online = document.getElementById('method_online');
        const row = document.getElementById('decline-row');
        if (online && row) {
            row.style.display = online.checked ? '' : 'none';
        }
    }
    syncDecline();

    // ★ Live tracking: poll the status endpoint; reload when the status
    // changes (simple polling — no WebSockets needed).
    const endpoint = <?= json_encode(url('/orders/' . (int) $order['id'] . '/status')) ?>;
    const currentStatus = <?= json_encode($order['status']) ?>;
    setInterval(async () => {
        try {
            const res = await fetch(endpoint, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            if (data.status !== currentStatus) {
                location.reload();
            }
        } catch (e) {
            // Network hiccup — the next tick will retry.
        }
    }, 5000);
</script>
