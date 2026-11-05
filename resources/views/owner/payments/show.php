<?php
/**
 * Payment receipt detail (Module 6) — restaurant view.
 */
$isOwner = in_array('restaurant_owner', auth_user()['roles'], true);
$statusBadge = fn (string $status): string => match ($status) {
    'success' => 'badge text-bg-success',
    'failed'  => 'badge text-bg-danger',
    'void'    => 'badge text-bg-secondary',
    default   => 'badge text-bg-warning text-dark',
};
?>
<div class="container py-4" style="max-width: 760px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h1 class="h4 mb-0">Payment #<?= (int) $payment['id'] ?></h1>
        <span class="<?= $statusBadge($payment['status']) ?>"><?= e(ucfirst($payment['status'])) ?></span>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h2 class="h6 text-muted text-uppercase mb-3">Receipt</h2>
            <table class="table table-sm mb-0">
                <tr><th class="ps-0 text-muted" style="width: 40%;">Order</th>
                    <td><a class="text-decoration-none"
                           href="<?= url('/owner/orders/' . (int) $order['id']) ?>"><code><?= e($order['order_no']) ?></code></a></td></tr>
                <tr><th class="ps-0 text-muted">Customer</th>
                    <td><?= e($customer['name']) ?> (<?= e($customer['email']) ?>)</td></tr>
                <tr><th class="ps-0 text-muted">Method</th><td><?= e(ucfirst($payment['method'])) ?></td></tr>
                <tr><th class="ps-0 text-muted">Amount</th>
                    <td>RM <?= e(number_format((float) $payment['amount'], 2)) ?></td></tr>
                <tr><th class="ps-0 text-muted">Reference</th>
                    <td><code><?= e($payment['transaction_ref'] ?? '—') ?></code></td></tr>
                <tr><th class="ps-0 text-muted">Paid at</th>
                    <td><?= $payment['paid_at'] ? e(date('d M Y, H:i', strtotime($payment['paid_at']))) : '—' ?></td></tr>
                <tr><th class="ps-0 text-muted">Recorded</th>
                    <td><?= e(date('d M Y, H:i', strtotime($payment['created_at']))) ?></td></tr>
            </table>

            <?php if ($isOwner && $payment['status'] === 'success'): ?>
                <hr>
                <form method="post" action="<?= url('/owner/payments/' . (int) $payment['id'] . '/void') ?>"
                      onsubmit="return confirm('Void this payment (e.g. refund)? The record is kept for audit.');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-danger btn-sm">Void payment</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
