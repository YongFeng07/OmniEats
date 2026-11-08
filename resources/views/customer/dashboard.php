<?php
/**
 * Customer hub (Grab/Foodpanda-style): personal stats, recent orders
 * with one-tap "Order again", saved restaurants and quick actions.
 *
 * Expects: $heading, $user, $recentOrders, $orderCount,
 * $completedCount, $favourites.
 */
$statusBadge = 'badge_class'; // shared helper in app/Helpers/functions.php
?>
<div class="container py-4" style="max-width: 960px;">
    <h1 class="h4 mb-1"><?= e($heading) ?></h1>
    <p class="text-muted small mb-4">Hi, <?= e($user['name']) ?> — hungry? Pick up where you left off.</p>

    <!-- Personal stats -->
    <div class="row g-3 mb-4">
        <div class="col-4">
            <div class="stat-tile text-center h-100">
                <p class="h3 stat-value mb-0"><?= (int) $orderCount ?></p>
                <p class="text-muted small mb-0">Total orders</p>
            </div>
        </div>
        <div class="col-4">
            <div class="stat-tile text-center h-100">
                <p class="h3 stat-value mb-0"><?= (int) $completedCount ?></p>
                <p class="text-muted small mb-0">Completed</p>
            </div>
        </div>
        <div class="col-4">
            <div class="stat-tile text-center h-100">
                <p class="h3 stat-value mb-0"><?= count($favourites) ?></p>
                <p class="text-muted small mb-0">Favourites</p>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <!-- Recent orders -->
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 text-muted text-uppercase mb-0">Recent orders</h2>
                        <a class="small text-decoration-none" href="<?= url('/orders') ?>">All orders →</a>
                    </div>

                    <?php foreach ($recentOrders as $order): ?>
                        <div class="d-flex flex-wrap align-items-center gap-2 py-2 border-bottom">
                            <div class="flex-grow-1">
                                <p class="mb-0">
                                    <a class="text-decoration-none fw-semibold text-dark"
                                       href="<?= url('/orders/' . (int) $order['id']) ?>">
                                        <?= e($order['restaurant_name']) ?>
                                    </a>
                                    <span class="<?= $statusBadge($order['status']) ?>">
                                        <?= e(ucwords(str_replace('_', ' ', $order['status']))) ?>
                                    </span>
                                </p>
                                <p class="small text-muted mb-0">
                                    RM <?= e(number_format((float) $order['total'], 2)) ?> ·
                                    <?= e(date('d M, H:i', strtotime($order['created_at']))) ?>
                                </p>
                            </div>
                            <div class="d-flex gap-1">
                                <a class="btn btn-sm btn-outline-primary"
                                   href="<?= url('/orders/' . (int) $order['id']) ?>">Track</a>
                                <?php if (in_array($order['status'], ['completed', 'cancelled'], true)): ?>
                                    <form method="post" action="<?= url('/orders/' . (int) $order['id'] . '/reorder') ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-primary">↻ Order again</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($recentOrders === []): ?>
                        <p class="text-muted small mb-0">
                            No orders yet — <a href="<?= url('/restaurants') ?>">find something tasty</a>.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-5 d-flex flex-column gap-4">
            <!-- Favourites -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 text-muted text-uppercase mb-0">❤️ Favourite restaurants</h2>
                        <a class="small text-decoration-none" href="<?= url('/restaurants') ?>">Browse →</a>
                    </div>

                    <?php foreach ($favourites as $fav): ?>
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <a class="text-decoration-none fw-semibold text-dark"
                                   href="<?= url('/restaurants/' . (int) $fav['id']) ?>"><?= e($fav['name']) ?></a>
                                <p class="small text-muted mb-0">
                                    <?= (int) $fav['is_open'] === 1 ? '● OPEN' : '● CLOSED' ?>
                                    <?= $fav['rating'] !== null ? ' · ⭐ ' . e(number_format((float) $fav['rating'], 1)) : '' ?>
                                </p>
                            </div>
                            <form method="post" action="<?= url('/restaurants/' . (int) $fav['id'] . '/favourite') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                        title="Remove from favourites">♥</button>
                            </form>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($favourites === []): ?>
                        <p class="text-muted small mb-0">
                            Tap the ♥ on a restaurant page to save it here.
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick actions -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h6 text-muted text-uppercase mb-3">Quick actions</h2>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn omnieats-btn btn-sm" href="<?= url('/restaurants') ?>">Browse restaurants</a>
                        <a class="btn btn-outline-secondary btn-sm" href="<?= url('/cart') ?>">My cart</a>
                        <a class="btn btn-outline-secondary btn-sm" href="<?= url('/orders') ?>">My orders</a>
                        <a class="btn btn-outline-secondary btn-sm" href="<?= url('/profile') ?>">Profile &amp; addresses</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
