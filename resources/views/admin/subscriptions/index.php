<?php
/**
 * Admin subscription monitoring (Module 4). Visiting this page also
 * runs the automatic expiry sweep.
 */
$statusBadge = 'badge_class'; // shared helper in app/Helpers/functions.php
?>
<div class="container py-4">
    <h1 class="h4 mb-3">Subscription Monitoring</h1>

    <form class="row g-2 mb-3" method="get" action="<?= url('/admin/subscriptions') ?>">
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">All statuses</option>
                <option value="active" <?= $fStatus === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="expired" <?= $fStatus === 'expired' ? 'selected' : '' ?>>Expired</option>
                <option value="cancelled" <?= $fStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>
        <div class="col-md-3 d-grid">
            <button type="submit" class="btn btn-outline-secondary">Filter</button>
        </div>
    </form>

    <p class="text-muted small mb-2"><?= count($subscriptions) ?> subscription record(s)</p>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Restaurant</th>
                        <th>Plan</th>
                        <th>Price paid</th>
                        <th>Starts</th>
                        <th>Ends</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subscriptions as $subscription): ?>
                        <tr>
                            <td><?= (int) $subscription['id'] ?></td>
                            <td><?= e($subscription['restaurant_name']) ?></td>
                            <td class="fw-semibold"><?= e($subscription['plan_name']) ?></td>
                            <td>RM <?= e(number_format((float) $subscription['price_paid'], 2)) ?></td>
                            <td class="small"><?= e(date('d M Y', strtotime($subscription['starts_at']))) ?></td>
                            <td class="small"><?= e(date('d M Y', strtotime($subscription['ends_at']))) ?></td>
                            <td><span class="<?= $statusBadge($subscription['status']) ?>">
                                <?= e(ucfirst($subscription['status'])) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($subscriptions === []): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No subscription records match the filter.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
