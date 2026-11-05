<?php
/**
 * Restaurant payment records (Module 6) with status filter.
 * Successful/voided records are kept for audit; pending/failed test
 * records may be deleted.
 */
$isOwner = in_array('restaurant_owner', auth_user()['roles'], true);
$statusBadge = fn (string $status): string => match ($status) {
    'success' => 'badge text-bg-success',
    'failed'  => 'badge text-bg-danger',
    'void'    => 'badge text-bg-secondary',
    default   => 'badge text-bg-warning text-dark',
};
?>
<div class="container py-4">
    <h1 class="h4 mb-3">Payments</h1>

    <form class="row g-2 mb-3" method="get" action="<?= url('/owner/payments') ?>">
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">All statuses</option>
                <option value="pending" <?= $fStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="success" <?= $fStatus === 'success' ? 'selected' : '' ?>>Success</option>
                <option value="failed" <?= $fStatus === 'failed' ? 'selected' : '' ?>>Failed</option>
                <option value="void" <?= $fStatus === 'void' ? 'selected' : '' ?>>Voided</option>
            </select>
        </div>
        <div class="col-md-3 d-grid">
            <button type="submit" class="btn btn-outline-secondary">Filter</button>
        </div>
    </form>

    <p class="text-muted small mb-2"><?= count($payments) ?> payment record(s)</p>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Method</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Paid at</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?= (int) $payment['id'] ?></td>
                            <td><code><?= e($payment['order_no']) ?></code></td>
                            <td><?= e($payment['customer_name']) ?></td>
                            <td><?= e(ucfirst($payment['method'])) ?></td>
                            <td>RM <?= e(number_format((float) $payment['amount'], 2)) ?></td>
                            <td><span class="<?= $statusBadge($payment['status']) ?>">
                                <?= e(ucfirst($payment['status'])) ?></span></td>
                            <td class="small"><?= $payment['paid_at'] ? e(date('d M H:i', strtotime($payment['paid_at']))) : '—' ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary"
                                   href="<?= url('/owner/payments/' . (int) $payment['id']) ?>">View</a>
                                <?php if ($isOwner && $payment['status'] === 'success'): ?>
                                    <form class="d-inline" method="post"
                                          action="<?= url('/owner/payments/' . (int) $payment['id'] . '/void') ?>"
                                          onsubmit="return confirm('Void this payment? The record is kept for audit.');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Void</button>
                                    </form>
                                <?php elseif ($isOwner && in_array($payment['status'], ['pending', 'failed'], true)): ?>
                                    <form class="d-inline" method="post"
                                          action="<?= url('/owner/payments/' . (int) $payment['id'] . '/delete') ?>"
                                          onsubmit="return confirm('Delete this pending/failed test record?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($payments === []): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No payment records match the filter.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
