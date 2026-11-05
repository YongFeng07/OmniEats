<?php
/**
 * Role dashboard. Implemented modules (M1) get real quick links;
 * the remaining modules are listed as upcoming.
 */
$roles = $user['roles'] ?? [];
$isAdmin = in_array('super_admin', $roles, true);
$isRestaurant = in_array('restaurant_owner', $roles, true) || in_array('restaurant_staff', $roles, true);
?>
<div class="container py-4">
    <h1 class="h4 mb-1"><?= e($heading) ?></h1>
    <p class="text-muted mb-4">
        Signed in as <strong><?= e($user['name']) ?></strong>
        (<?= e(implode(', ', $roles)) ?>)
        <?php if ($tenant): ?> — <?= e($tenant['name']) ?><?php endif; ?>
    </p>

    <?php if ($isRestaurant && !$tenant): ?>
        <div class="alert alert-warning">
            Your restaurant profile is not set up yet. Restaurant registration opens in
            Module 2 (next phase) — you will be able to create your restaurant, branches
            and menu there.
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        <?php if ($isAdmin): ?>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h5">User Management</h2>
                        <p class="text-muted small">Create accounts, assign roles, deactivate users.</p>
                        <a class="btn omnieats-btn btn-sm" href="<?= url('/admin/users') ?>">Manage users</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h5">My Profile</h2>
                    <p class="text-muted small">Update your name, email, phone and password.</p>
                    <a class="btn btn-outline-secondary btn-sm" href="<?= url('/profile') ?>">Edit profile</a>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info">
        <p class="mb-2 fw-semibold">Upcoming in Phase 5:</p>
        <ul class="mb-0">
            <?php if ($isAdmin): ?>
                <li>Restaurant Management &amp; Approval (M2)</li>
                <li>Subscription Plan Management (M4)</li>
                <li>Subscription Monitoring &amp; Activity Overview (M4)</li>
            <?php elseif ($isRestaurant): ?>
                <li>Restaurant Profile &amp; Branch Management (M2)</li>
                <li>Menu Categories &amp; Food Items (M3)</li>
                <li>Order Management (M5)</li>
                <li>Payment &amp; Fulfilment Management (M6)</li>
                <li>Subscription Management (M4)</li>
            <?php else: ?>
                <li>Restaurant Listing &amp; Menus (M2/M3)</li>
                <li>Shopping Cart &amp; Checkout (M5)</li>
                <li>Payment (M6)</li>
                <li>Order Tracking &amp; History (M5)</li>
            <?php endif; ?>
        </ul>
    </div>
</div>
