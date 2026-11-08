<?php
/**
 * Role dashboard (M1) — quick links and status for the signed-in user.
 * Admin gets platform stats; owners get restaurant + subscription cards;
 * customers get a browsing hub. All six modules are implemented.
 */
$roles = $user['roles'] ?? [];
$isAdmin = in_array('super_admin', $roles, true);
$isRestaurant = in_array('restaurant_owner', $roles, true) || in_array('restaurant_staff', $roles, true);
$isOwner = in_array('restaurant_owner', $roles, true);
$statusBadge = 'badge_class'; // shared helper in app/Helpers/functions.php
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

    <?php if ($isAdmin && isset($stats)): ?>
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3 col-lg-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3 text-center">
                        <div class="h4 mb-0"><?= (int) $stats['users'] ?></div>
                        <div class="text-muted small">Users</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3 text-center">
                        <div class="h4 mb-0"><?= (int) $stats['restaurants'] ?></div>
                        <div class="text-muted small">Restaurants <span class="text-warning">(<?= (int) $stats['restaurants_pending'] ?> pending)</span></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3 text-center">
                        <div class="h4 mb-0"><?= (int) $stats['active_subscriptions'] ?></div>
                        <div class="text-muted small">Active subs</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3 text-center">
                        <div class="h4 mb-0"><?= (int) $stats['orders'] ?></div>
                        <div class="text-muted small">Orders</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3 text-center">
                        <div class="h4 mb-0">RM <?= e(number_format($stats['revenue'], 2)) ?></div>
                        <div class="text-muted small">Successful payments (all restaurants)</div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        <?php if ($isAdmin): ?>            <div class="col-md-4">
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
                    <?php if ($isAdmin || $isRestaurant): ?>
                        <h2 class="h5">My Profile</h2>
                        <p class="text-muted small">Update your name, email, phone and password.</p>
                        <a class="btn btn-outline-secondary btn-sm" href="<?= url('/profile') ?>">Edit profile</a>
                    <?php else: ?>
                        <h2 class="h5">Order food</h2>
                        <p class="text-muted small">Browse restaurants, add to cart and track your orders.</p>
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn omnieats-btn btn-sm" href="<?= url('/restaurants') ?>">Browse restaurants</a>
                            <a class="btn btn-outline-secondary btn-sm" href="<?= url('/cart') ?>">My cart</a>
                            <a class="btn btn-outline-secondary btn-sm" href="<?= url('/orders') ?>">My orders</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
