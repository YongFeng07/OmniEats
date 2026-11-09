<?php
/**
 * Rider's own profile (Delivery & Rider module — Student 3).
 * Vehicle details are managed by the admin; the rider sees them here
 * and controls only availability.
 *
 * Expects: $heading, $profile, $user.
 */
?>
<div class="container py-4" style="max-width: 640px;">
    <h1 class="h4 mb-4"><?= e($heading) ?></h1>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h2 class="h6 text-muted text-uppercase mb-3">Account</h2>
            <p class="mb-1"><strong><?= e($user['name']) ?></strong></p>
            <p class="mb-1"><?= e($user['email']) ?></p>
            <p class="mb-3"><?= e($user['phone'] ?? '') ?></p>

            <h2 class="h6 text-muted text-uppercase mb-3">Vehicle</h2>
            <p class="mb-1">Type: <strong><?= e($profile['vehicle_type'] ?? '—') ?></strong></p>
            <p class="mb-3">Plate: <code><?= e($profile['vehicle_plate'] ?? '—') ?></code></p>

            <h2 class="h6 text-muted text-uppercase mb-3">Account status</h2>
            <p class="mb-1">
                <?php if ((int) $profile['is_active'] === 1): ?>
                    <span class="badge text-bg-success">Active</span>
                <?php else: ?>
                    <span class="badge text-bg-danger">Suspended</span>
                <?php endif; ?>
                <?php if ((int) $profile['is_available'] === 1): ?>
                    <span class="badge text-bg-primary">Available</span>
                <?php else: ?>
                    <span class="badge text-bg-secondary">Unavailable</span>
                <?php endif; ?>
            </p>
            <p class="text-muted small mb-0">
                Vehicle details are managed by the platform administrator.
                Use the dashboard to change your availability.
            </p>
        </div>
    </div>
</div>
