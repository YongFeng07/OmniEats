<?php
/**
 * Rider Portal dashboard (Delivery & Rider module — Student 3).
 * Availability toggle (the assignment rule reads this server-side)
 * plus the rider's current task, if any.
 *
 * Expects: $heading, $profile, $active (fulfilment row or null).
 */
?>
<div class="container py-4" style="max-width: 960px;">
    <h1 class="h4 mb-1"><?= e($heading) ?></h1>
    <p class="text-muted small mb-4">
        Hi, <?= e(auth_user()['name']) ?> — your availability decides whether the
        administrator can assign deliveries to you.
    </p>

    <?php if ((int) $profile['is_active'] !== 1): ?>
        <div class="alert alert-danger">
            Your rider account is <strong>suspended</strong>. Contact the platform administrator.
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-5">
            <div class="stat-tile h-100">
                <p class="text-muted small mb-1">Status</p>
                <?php if ((int) $profile['is_available'] === 1): ?>
                    <p class="h4 stat-value mb-0 text-success">🟢 Available</p>
                    <p class="small text-muted mb-3">You can receive new assignments.</p>
                    <form method="post" action="<?= url('/rider/toggle-available') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-secondary">Go off shift</button>
                    </form>
                <?php else: ?>
                    <p class="h4 stat-value mb-0 text-secondary">⚪ Unavailable</p>
                    <p class="small text-muted mb-3">You will not receive new assignments.</p>
                    <form method="post" action="<?= url('/rider/toggle-available') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn omnieats-btn">Go on shift</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-7">
            <div class="stat-tile h-100">
                <p class="text-muted small mb-1">Current task</p>
                <?php if ($active !== null): ?>
                    <p class="h5 mb-1 text-dark">
                        <?= e($active['order_no']) ?> ·
                        <span class="badge text-bg-primary"><?= e(ucwords(str_replace('_', ' ', $active['status']))) ?></span>
                    </p>
                    <p class="small text-muted mb-3">
                        <?= e($active['recipient_name'] ?? 'Customer') ?> —
                        <?= e($active['delivery_address'] ?? '') ?>
                    </p>
                    <a class="btn omnieats-btn btn-sm" href="<?= url('/rider/tasks') ?>">Open task</a>
                <?php else: ?>
                    <p class="h5 text-muted mb-3">No active delivery.</p>
                    <p class="small text-muted mb-0">
                        New assignments from the administrator appear here.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-md-6">
            <div class="stat-tile h-100">
                <h2 class="h6 mb-2">My vehicle</h2>
                <p class="mb-1"><?= e($profile['vehicle_type'] ?? '—') ?></p>
                <p class="mb-0"><code><?= e($profile['vehicle_plate'] ?? '—') ?></code></p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="stat-tile h-100">
                <h2 class="h6 mb-2">Quick links</h2>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-sm btn-outline-secondary" href="<?= url('/rider/tasks') ?>">My tasks</a>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= url('/rider/history') ?>">History</a>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= url('/rider/profile') ?>">Profile</a>
                </div>
            </div>
        </div>
    </div>
</div>
