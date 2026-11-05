<?php
/**
 * Placeholder role dashboard (Phase 4). Each module's real dashboard
 * pages replace the "coming in Phase 5" list below.
 */
$roles = $user['roles'] ?? [];
?>
<div class="container py-4">
    <h1 class="h4 mb-1"><?= e($heading) ?></h1>
    <p class="text-muted mb-4">
        Signed in as <strong><?= e($user['name']) ?></strong>
        (<?= e(implode(', ', $roles)) ?>)
        <?php if ($tenant): ?> — <?= e($tenant['name']) ?><?php endif; ?>
    </p>

    <div class="alert alert-info">
        <p class="mb-2 fw-semibold">Module dashboards land here in Phase 5:</p>
        <ul class="mb-0">
            <?php if (in_array('super_admin', $roles, true)): ?>
                <li>User Management (M1)</li>
                <li>Restaurant Management &amp; Approval (M2)</li>
                <li>Subscription Plan Management (M4)</li>
                <li>Subscription Monitoring &amp; Activity Overview (M4)</li>
            <?php elseif (in_array('restaurant_owner', $roles, true) || in_array('restaurant_staff', $roles, true)): ?>
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
                <li>Profile Management (M1)</li>
            <?php endif; ?>
        </ul>
    </div>
</div>
