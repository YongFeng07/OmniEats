<?php
/**
 * Admin voucher list (Promotion & Voucher module — Student 1).
 * Search + active filter; archive/restore is the soft DELETE.
 */
?>
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h1 class="h4 mb-0">Vouchers</h1>
        <a class="btn omnieats-btn btn-sm" href="<?= url('/admin/vouchers/create') ?>">+ Create voucher</a>
    </div>

    <form class="row g-2 mb-3" method="get" action="<?= url('/admin/vouchers') ?>">
        <div class="col-md-4">
            <input type="text" name="q" class="form-control" placeholder="Search code or title"
                   value="<?= e($q) ?>">
        </div>
        <div class="col-md-3">
            <select name="active" class="form-select">
                <option value="">All statuses</option>
                <option value="active" <?= $fActive === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $fActive === 'inactive' ? 'selected' : '' ?>>Archived</option>
            </select>
        </div>
        <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-outline-secondary">Search</button>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Title</th>
                        <th>Discount</th>
                        <th>Min order</th>
                        <th>Restaurant</th>
                        <th>Validity</th>
                        <th>Used</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vouchers as $voucher): ?>
                        <tr>
                            <td><code><?= e($voucher['code']) ?></code></td>
                            <td><?= e($voucher['title']) ?></td>
                            <td>
                                <?= $voucher['discount_type'] === 'percent'
                                    ? (int) $voucher['discount_value'] . '%'
                                    : 'RM ' . number_format((float) $voucher['discount_value'], 2) ?>
                                <?php if ($voucher['max_discount'] !== null): ?>
                                    <span class="text-muted small">(cap RM <?= number_format((float) $voucher['max_discount'], 2) ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td>RM <?= number_format((float) $voucher['min_order'], 2) ?></td>
                            <td><?= e($voucher['restaurant_name'] ?? 'All restaurants') ?></td>
                            <td class="small">
                                <?= e(date('d M', strtotime($voucher['starts_at']))) ?> →
                                <?= e(date('d M Y', strtotime($voucher['ends_at']))) ?>
                            </td>
                            <td><?= (int) $voucher['used_count'] ?>/<?= (int) $voucher['usage_limit'] ?></td>
                            <td>
                                <?php if ((int) $voucher['is_active'] === 1): ?>
                                    <span class="badge text-bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Archived</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary"
                                   href="<?= url('/admin/vouchers/' . (int) $voucher['id'] . '/edit') ?>">Edit</a>
                                <form class="d-inline" method="post"
                                      action="<?= url('/admin/vouchers/' . (int) $voucher['id'] . '/toggle') ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                        <?= (int) $voucher['is_active'] === 1 ? 'Archive' : 'Restore' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($vouchers === []): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">No vouchers match.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
