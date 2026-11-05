<?php
/**
 * Restaurant fulfilment records (Module 6): delivery / self-pickup
 * execution with validated status transitions.
 */
$statusBadge = 'badge_class'; // shared helper in app/Helpers/functions.php
?>
<div class="container py-4">
    <h1 class="h4 mb-3">Fulfilments</h1>

    <form class="row g-2 mb-3" method="get" action="<?= url('/owner/fulfilments') ?>">
        <div class="col-md-4">
            <select name="status" class="form-select">
                <option value="">All statuses</option>
                <?php foreach (['pending', 'out_for_delivery', 'ready_for_collection', 'completed', 'cancelled'] as $status): ?>
                    <option value="<?= e($status) ?>" <?= $fStatus === $status ? 'selected' : '' ?>>
                        <?= e(ucwords(str_replace('_', ' ', $status))) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-grid">
            <button type="submit" class="btn btn-outline-secondary">Filter</button>
        </div>
    </form>

    <p class="text-muted small mb-2"><?= count($fulfilments) ?> fulfilment record(s)</p>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Details</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fulfilments as $fulfilment): ?>
                        <tr>
                            <td><?= (int) $fulfilment['id'] ?></td>
                            <td><code><?= e($fulfilment['order_no']) ?></code></td>
                            <td><?= e($fulfilment['customer_name']) ?></td>
                            <td><?= e(ucfirst($fulfilment['type'])) ?></td>
                            <td class="small"><?= e($fulfilment['address'] ?? '—') ?></td>
                            <td><span class="<?= $statusBadge($fulfilment['status']) ?>">
                                <?= e(ucwords(str_replace('_', ' ', $fulfilment['status']))) ?></span></td>
                            <td class="text-end">
                                <?php if ($fulfilment['status'] === 'pending'): ?>
                                    <form class="d-inline" method="post"
                                          action="<?= url('/owner/fulfilments/' . (int) $fulfilment['id'] . '/transition') ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="status"
                                               value="<?= $fulfilment['type'] === 'delivery' ? 'out_for_delivery' : 'ready_for_collection' ?>">
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <?= $fulfilment['type'] === 'delivery' ? 'Out for delivery' : 'Ready for collection' ?>
                                        </button>
                                    </form>
                                <?php elseif (in_array($fulfilment['status'], ['out_for_delivery', 'ready_for_collection'], true)): ?>
                                    <form class="d-inline" method="post"
                                          action="<?= url('/owner/fulfilments/' . (int) $fulfilment['id'] . '/transition') ?>"
                                          onsubmit="return confirm('Mark this fulfilment as completed?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="status" value="completed">
                                        <button type="submit" class="btn btn-sm btn-success">Complete</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($fulfilments === []): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No fulfilment records match the filter.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
