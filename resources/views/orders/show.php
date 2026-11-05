<?php
/**
 * Customer order detail (Module 5): items (historical snapshots),
 * fulfilment info, totals, ★ live status timeline with JS polling,
 * cancellation while eligible, and the payment section (Module 6
 * replaces the placeholder alert with the simulated payment form).
 */
$statusBadge = fn (string $status): string => match ($status) {
    'pending_payment' => 'badge text-bg-warning text-dark',
    'confirmed'       => 'badge text-bg-info text-dark',
    'preparing'       => 'badge text-bg-primary',
    'ready'           => 'badge text-bg-warning text-dark',
    'completed'       => 'badge text-bg-success',
    'cancelled'       => 'badge text-bg-secondary',
    default           => 'badge text-bg-secondary',
};
?>
<div class="container py-4" style="max-width: 960px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h1 class="h4 mb-0">Order <code><?= e($order['order_no']) ?></code></h1>
        <span class="<?= $statusBadge($order['status']) ?>">
            <?= e(ucwords(str_replace('_', ' ', $order['status']))) ?>
        </span>
    </div>

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
                                    <td><?= e($line['food_name']) ?></td>
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
