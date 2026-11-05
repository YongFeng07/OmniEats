<?php
/**
 * Role dashboard. Implemented modules (M1 + M2) get real quick links
 * and actions; the remaining modules are listed as upcoming.
 */
$roles = $user['roles'] ?? [];
$isAdmin = in_array('super_admin', $roles, true);
$isRestaurant = in_array('restaurant_owner', $roles, true) || in_array('restaurant_staff', $roles, true);
$isOwner = in_array('restaurant_owner', $roles, true);
$statusBadge = fn (string $status): string => match ($status) {
    'approved'    => 'badge text-bg-success',
    'pending'     => 'badge text-bg-warning text-dark',
    'deactivated' => 'badge text-bg-secondary',
    default       => 'badge text-bg-secondary',
};
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
            Your restaurant profile is not set up yet.
            <a class="alert-link" href="<?= url('/owner/restaurant/create') ?>">Register your restaurant</a>
            to start managing your business on OmniEats.
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
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h5">Restaurant Management</h2>
                        <p class="text-muted small">Review registrations, approve or deactivate restaurants.</p>
                        <a class="btn omnieats-btn btn-sm" href="<?= url('/admin/restaurants') ?>">Manage restaurants</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($isRestaurant && $tenant): ?>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h5">My Restaurant</h2>
                        <p class="mb-2"><?= e($tenant['name']) ?>
                            <span class="<?= $statusBadge($tenant['status']) ?>"><?= e(ucfirst($tenant['status'])) ?></span>
                        </p>
                        <p class="text-muted small">
                            Availability:
                            <?php if ((int) $tenant['is_open'] === 1): ?>
                                <span class="badge text-bg-success">OPEN</span>
                            <?php else: ?>
                                <span class="badge text-bg-danger">CLOSED</span>
                            <?php endif; ?>
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= url('/owner/restaurant/edit') ?>">Edit profile</a>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= url('/owner/branches') ?>">Branches</a>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= url('/owner/foods') ?>">Menu</a>
                            <?php if ($isOwner): ?>
                                <form method="post" action="<?= url('/owner/restaurant/toggle-open') ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm omnieats-btn">
                                        <?= (int) $tenant['is_open'] === 1 ? 'Close restaurant' : 'Open restaurant' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($isOwner): ?>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h2 class="h5">Subscription</h2>
                            <?php if ($subscriptionInfo['subscription'] === null): ?>
                                <p class="text-warning-emphasis small mb-2">No active subscription —
                                    branch and food item creation is blocked.</p>
                                <a class="btn omnieats-btn btn-sm" href="<?= url('/owner/subscription') ?>">Choose a plan</a>
                            <?php else: ?>
                                <p class="mb-2">
                                    <?= e($subscriptionInfo['plan']['name'] ?? 'Plan') ?>
                                    <span class="badge text-bg-success">Active</span>
                                </p>
                                <p class="text-muted small mb-2">Ends <?= e(date('d M Y', strtotime($subscriptionInfo['subscription']['ends_at']))) ?></p>
                                <a class="btn btn-outline-secondary btn-sm" href="<?= url('/owner/subscription') ?>">Manage subscription</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
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
                <li>Platform Activity Overview (M4)</li>
            <?php elseif ($isRestaurant): ?>
                <li>Order Management (M5)</li>
                <li>Payment &amp; Fulfilment Management (M6)</li>
            <?php else: ?>
                <li>Shopping Cart &amp; Checkout (M5)</li>
                <li>Payment (M6)</li>
                <li>Order Tracking &amp; History (M5)</li>
            <?php endif; ?>
        </ul>
    </div>
</div>
