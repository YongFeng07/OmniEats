<?php
/**
 * Merchant dashboard (owner & staff) — Cream Soft UI.
 * Today's sales stat tiles + incoming orders (confirmed, awaiting the
 * merchant) with an "Accept order" button that advances the order to
 * preparing through the existing validated transition endpoint.
 *
 * Expects: $heading, $user, $tenant, $subscriptionInfo,
 * $todaySales, $todayOrders, $incomingOrders.
 */
$isOwner = in_array('restaurant_owner', $user['roles'], true);
$statusBadge = 'badge_class'; // shared helper in app/Helpers/functions.php
?>
<div class="container py-4">
    <h1 class="h3 mb-1"><?= e($heading) ?></h1>
    <p class="text-muted small mb-4">
        <?= e($tenant['name']) ?> ·
        <span class="<?= $statusBadge($tenant['status']) ?>"><?= e(ucfirst($tenant['status'])) ?></span>
        <?php if ((int) $tenant['is_open'] === 1): ?>
            <span class="badge text-bg-success">OPEN</span>
        <?php else: ?>
            <span class="badge text-bg-danger">CLOSED</span>
        <?php endif; ?>
    </p>

    <?php if ($isOwner && $subscriptionInfo['subscription'] === null): ?>
        <div class="alert alert-warning">
            No active subscription — new branch/food creation is blocked.
            <a class="alert-link" href="<?= url('/owner/subscription') ?>">Choose a plan</a>.
        </div>
    <?php endif; ?>

    <!-- Sales & orders stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-tile h-100">
                <p class="text-muted small mb-1">Today's sales</p>
                <p class="h4 stat-value mb-0">RM <?= e(number_format($todaySales, 2)) ?></p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-tile h-100">
                <p class="text-muted small mb-1">This month</p>
                <p class="h4 stat-value mb-0">RM <?= e(number_format($monthSales, 2)) ?></p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-tile h-100">
                <p class="text-muted small mb-1">Today's orders</p>
                <p class="h4 stat-value mb-0"><?= (int) $todayOrders ?></p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-tile h-100">
                <p class="text-muted small mb-1">Subscription</p>
                <?php if ($subscriptionInfo['subscription'] !== null): ?>
                    <p class="h6 stat-value mb-0"><?= e($subscriptionInfo['plan']['name'] ?? 'Plan') ?></p>
                    <p class="small text-muted mb-0">Ends <?= e(date('d M Y', strtotime($subscriptionInfo['subscription']['ends_at']))) ?></p>
                <?php else: ?>
                    <p class="h6 mb-0"><a class="text-decoration-none" href="<?= url('/owner/subscription') ?>">Choose a plan →</a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Incoming orders -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Incoming orders</h2>
        <a class="small text-decoration-none" href="<?= url('/owner/orders') ?>">All orders →</a>
    </div>

    <div class="d-flex flex-column gap-3 mb-4">
        <?php foreach ($incomingOrders as $order): ?>
            <div class="incoming-order d-flex flex-wrap align-items-center gap-3">
                <div class="flex-grow-1">
                    <p class="fw-bold mb-1 text-dark">
                        <?= e($order['customer_name']) ?> ·
                        <code><?= e($order['order_no']) ?></code>
                        <span class="<?= $statusBadge($order['status']) ?>"><?= e(ucfirst($order['status'])) ?></span>
                    </p>
                    <p class="small text-muted mb-0">
                        <?= (int) $order['item_count'] ?> item(s) ·
                        RM <?= e(number_format((float) $order['total'], 2)) ?> ·
                        <?= e(ucfirst($order['fulfilment_type'])) ?> ·
                        <?= e(date('H:i', strtotime($order['created_at']))) ?>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-secondary btn-sm"
                       href="<?= url('/owner/orders/' . (int) $order['id']) ?>">View</a>
                    <form method="post" action="<?= url('/owner/orders/' . (int) $order['id'] . '/transition') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="status" value="preparing">
                        <button type="submit" class="btn btn-primary btn-sm">Accept order</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if ($incomingOrders === []): ?>
            <div class="alert alert-info mb-0">No incoming orders right now — new paid orders appear here.</div>
        <?php endif; ?>
    </div>

    <!-- Quick actions -->
    <div class="row g-4">
        <div class="col-md-4">
            <div class="stat-tile h-100">
                <h2 class="h6 mb-2">My Restaurant</h2>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-sm btn-outline-secondary" href="<?= url('/owner/restaurant/edit') ?>">Edit profile</a>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= url('/owner/branches') ?>">Branches</a>
                    <?php if ($isOwner): ?>
                        <form method="post" action="<?= url('/owner/restaurant/toggle-open') ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-primary">
                                <?= (int) $tenant['is_open'] === 1 ? 'Close restaurant' : 'Open restaurant' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-tile h-100">
                <h2 class="h6 mb-2">My Menu</h2>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-sm btn-outline-secondary" href="<?= url('/owner/foods') ?>">Food items</a>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= url('/owner/categories') ?>">Categories</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-tile h-100">
                <h2 class="h6 mb-2">Orders &amp; fulfilment</h2>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-sm btn-outline-secondary" href="<?= url('/owner/orders') ?>">Orders</a>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= url('/owner/payments') ?>">Payments</a>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= url('/owner/fulfilments') ?>">Fulfilments</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // ★ GrabMerchant-style live incoming orders: poll the confirmed
    // count; when it changes, refresh the dashboard so new paid orders
    // appear without any manual action.
    (() => {
        const endpoint = <?= json_encode(url('/owner/orders/poll')) ?>;
        const current = <?= (int) count($incomingOrders) ?>;
        setInterval(async () => {
            try {
                const res = await fetch(endpoint, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                if (data.confirmed !== current) {
                    location.reload();
                }
            } catch (e) {
                // Network hiccup — the next tick will retry.
            }
        }, 10000);
    })();
</script>
