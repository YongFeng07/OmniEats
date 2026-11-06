<?php
/**
 * Master layout shared by all pages (header + nav + footer).
 * Expects: $title, $content (the rendered page body).
 *
 * Cream Soft UI theme: white sticky navbar with pill search, soft
 * shadow, terracotta accents. All links/guards unchanged — UI only.
 */
$user = auth_user();
$roleHome = $user ? App\Middleware\AuthMiddleware::homeFor($user) : '/';
$isAdmin = $user && in_array('super_admin', $user['roles'], true);
$isRestaurant = $user && (in_array('restaurant_owner', $user['roles'], true)
    || in_array('restaurant_staff', $user['roles'], true));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'OmniEats') ?> · OmniEats</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🍽️</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= url('/assets/vendor/bootstrap/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= url('/css/cream-theme.css') ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg omnieats-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?= url('/') ?>">
            <span class="omnieats-logo">🍽️</span>
            <span class="omnieats-logo-text">OmniEats</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <div class="d-lg-flex flex-lg-grow-1 align-items-lg-center justify-content-lg-center mx-lg-4 my-3 my-lg-0">
                <form class="omnieats-search-wrap w-100" method="get" action="<?= url('/restaurants') ?>">
                    <span class="omnieats-search-icon">🔍</span>
                    <input type="search" name="q" class="omnieats-search w-100"
                           placeholder="Search restaurants or food…"
                           aria-label="Search restaurants" value="<?= e($_GET['q'] ?? '') ?>">
                </form>
            </div>

            <ul class="navbar-nav ms-lg-auto align-items-lg-center gap-lg-1">
                <li class="nav-item"><a class="nav-link" href="<?= url('/') ?>">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('/restaurants') ?>">Restaurants</a></li>
                <?php if ($user): ?>
                    <?php if (in_array('customer', $user['roles'], true)): ?>
                        <?php $cartCount = App\Services\CartService::cartCount((int) $user['id']); ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('/cart') ?>">
                                🛒 Cart<?= $cartCount > 0 ? ' <span class="badge text-bg-primary" data-cart-count>' . $cartCount . '</span>' : '' ?>
                            </a>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="<?= url('/orders') ?>">My Orders</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="<?= url('/profile') ?>">Profile</a></li>
                    <?php if ($isAdmin): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= url('/admin/users') ?>">Users</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= url('/admin/restaurants') ?>">Tenants</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= url('/admin/plans') ?>">Plans</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= url('/admin/subscriptions') ?>">Subscriptions</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= url('/admin/activity') ?>">Activity</a></li>
                    <?php endif; ?>
                    <?php if ($isRestaurant): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url($user['restaurant_id'] ? '/owner/restaurant/edit' : '/owner/restaurant/create') ?>">My Restaurant</a>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="<?= url('/owner/branches') ?>">Branches</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= url('/owner/foods') ?>">My Menu</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= url('/owner/orders') ?>">Incoming Orders</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= url('/owner/payments') ?>">Payments</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= url('/owner/fulfilments') ?>">Fulfilments</a></li>
                        <?php if (in_array('restaurant_owner', $user['roles'], true)): ?>
                            <li class="nav-item"><a class="nav-link" href="<?= url('/owner/subscription') ?>">Subscription</a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                    <li class="nav-item">
                        <span class="navbar-text me-lg-2">Hi, <?= e($user['name']) ?></span>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="<?= url($roleHome) ?>">Dashboard</a></li>
                    <li class="nav-item ms-lg-2">
                        <form method="post" action="<?= url('/logout') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-light">Sign out</button>
                        </form>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= url('/register') ?>">Register</a></li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-sm btn-light px-4" href="<?= url('/login') ?>">Sign in</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main>
    <?php if ($error = flash('error')): ?>
        <div class="container mt-4">
            <div class="alert alert-danger mb-0"><?= e($error) ?></div>
        </div>
    <?php endif; ?>

    <?php if ($success = flash('success')): ?>
        <div class="container mt-4">
            <div class="alert alert-success mb-0"><?= e($success) ?></div>
        </div>
    <?php endif; ?>

    <?= $content ?? '' ?>
</main>

<footer class="omnieats-footer mt-5">
    <div class="container d-flex flex-wrap justify-content-between py-4 gap-2">
        <span>© <?= date('Y') ?> OmniEats — TAR UMT AMCS2194 Mini Project</span>
        <span>Multi-Restaurant Food Ordering SaaS Platform</span>
    </div>
</footer>

<script src="<?= url('/assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
