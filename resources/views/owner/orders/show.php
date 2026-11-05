<?php
/**
 * Restaurant order detail (Module 5): items, customer, fulfilment,
 * timeline, and validated status transition actions.
 */
$statusLabel = fn (string $status): string => ucwords(str_replace('_', ' ', $status));

// Keyed by the NEXT status: the button that advances the order there.
// (pending_payment → confirmed is handled by the payment flow in M6.)
$actionButtons = [
    'preparing' => ['label' => 'Start preparing', 'class' => 'btn-primary'],
    'ready'     => ['label' => 'Mark ready', 'class' => 'btn-warning'],
    'completed' => ['label' => 'Complete order', 'class' => 'btn-success'],
];
?>
<div class="container py-4" style="max-width: 960px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h1 class="h4 mb-0">Order <code><?= e($order['order_no']) ?></code></h1>
        <span class="badge text-bg-secondary"><?= e($statusLabel($order['status'])) ?></span>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h2 class="h6 text-muted text-uppercase mb-3">Status timeline</h2>
            <?php view('shared/order_timeline', ['order' => $order, 'history' => $history], null); ?>

            <?php if (in_array($order['status'], ['pending_payment', 'confirmed', 'preparing', 'ready'], true)): ?>
                <hr class="mt-4">
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($transitions as $next): ?>
                        <?php if ($next === 'cancelled') { continue; } ?>
                        <?php if (isset($actionButtons[$next])): ?>
                            <form method="post" action="<?= url('/owner/orders/' . (int) $order['id'] . '/transition') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="status" value="<?= e($next) ?>">
                                <button type="submit" class="btn btn-sm <?= e($actionButtons[$next]['class']) ?>">
                                    <?= e($actionButtons[$next]['label']) ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php if (in_array('cancelled', $transitions, true)): ?>
                        <form method="post" class="d-flex gap-2"
                              action="<?= url('/owner/orders/' . (int) $order['id'] . '/transition') ?>"
                              onsubmit="return confirm('Cancel this order?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="status" value="cancelled">
                            <input type="text" name="note" class="form-control form-control-sm"
                                   placeholder="Cancellation reason (optional)" maxlength="255">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Cancel order</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
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
                        <p class="text-muted small mt-3 mb-0">Customer note: <?= e($order['note']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h2 class="h6 text-muted text-uppercase mb-3">Customer &amp; fulfilment</h2>
                    <p class="mb-1 fw-semibold"><?= e($customer['name']) ?></p>
                    <p class="text-muted small mb-3"><?= e($customer['email']) ?><?= !empty($customer['phone']) ? ' · ' . e($customer['phone']) : '' ?></p>
                    <p class="small mb-1"><strong>Fulfilment:</strong> <?= e(ucfirst($order['fulfilment_type'])) ?>
                        <?php if ($branch): ?> · <?= e($branch['name']) ?><?php endif; ?></p>
                    <?php if ($order['fulfilment_type'] === 'delivery'): ?>
                        <p class="small mb-1"><strong>Address:</strong> <?= e($order['delivery_address']) ?></p>
                        <p class="small mb-0"><strong>Phone:</strong> <?= e($order['delivery_phone']) ?></p>
                    <?php endif; ?>
                    <?php if ($fulfilment !== null): ?>
                        <hr>
                        <p class="small mb-1"><strong>Fulfilment status:</strong>
                            <span class="badge text-bg-secondary">
                                <?= e(ucwords(str_replace('_', ' ', $fulfilment['status']))) ?>
                            </span>
                            <?php if (!empty($fulfilment['completed_at'])): ?>
                                · completed <?= e(date('d M H:i', strtotime($fulfilment['completed_at']))) ?>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h2 class="h6 text-muted text-uppercase mb-3">Payment</h2>
                    <?php if ($payment === null): ?>
                        <p class="text-muted small mb-0">No payment record yet (order not paid).</p>
                    <?php else: ?>
                        <p class="small mb-1"><strong>Method:</strong> <?= e(ucfirst($payment['method'])) ?></p>
                        <p class="small mb-1"><strong>Amount:</strong> RM <?= e(number_format((float) $payment['amount'], 2)) ?></p>
                        <?php if (!empty($payment['transaction_ref'])): ?>
                            <p class="small mb-1"><strong>Reference:</strong> <code><?= e($payment['transaction_ref']) ?></code></p>
                        <?php endif; ?>
                        <p class="small mb-0">
                            <strong>Status:</strong>
                            <?php if ($payment['status'] === 'success'): ?>
                                <span class="badge text-bg-success">Paid</span>
                            <?php elseif ($payment['status'] === 'failed'): ?>
                                <span class="badge text-bg-danger">Failed</span>
                            <?php elseif ($payment['status'] === 'void'): ?>
                                <span class="badge text-bg-secondary">Voided</span>
                            <?php else: ?>
                                <span class="badge text-bg-warning text-dark">Pending</span>
                            <?php endif; ?>
                            <?php if ($payment['paid_at']): ?>
                                · <?= e(date('d M H:i', strtotime($payment['paid_at']))) ?>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h6 text-muted text-uppercase mb-3">History</h2>
                    <?php foreach (array_reverse($history) as $entry): ?>
                        <div class="d-flex justify-content-between small mb-2">
                            <span>
                                <span class="text-muted"><?= e($entry['from_status'] ?? '—') ?> →</span>
                                <strong><?= e($statusLabel($entry['to_status'])) ?></strong>
                                <?= !empty($entry['actor_name']) ? ' by ' . e($entry['actor_name']) : '' ?>
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
