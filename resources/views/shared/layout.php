<?php
/**
 * Master layout shared by all pages.
 * Expects: $title, $content (the rendered page body).
 * Navigation grows as each module's pages are implemented.
 */
$user = auth_user();
$roleHome = $user ? App\Middleware\AuthMiddleware::homeFor($user) : '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'OmniEats') ?> · OmniEats</title>
    <link href="<?= url('/assets/vendor/bootstrap/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= url('/assets/css/app.css') ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark omnieats-navbar">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= url('/') ?>">🍽️ OmniEats</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item"><a class="nav-link" href="<?= url('/') ?>">Home</a></li>
                <?php if ($user): ?>
                    <li class="nav-item">
                        <span class="navbar-text me-lg-2">Hi, <?= e($user['name']) ?></span>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="<?= url($roleHome) ?>">Dashboard</a></li>
                    <li class="nav-item">
                        <form method="post" action="<?= url('/logout') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-light">Sign out</button>
                        </form>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="btn btn-sm btn-light px-3" href="<?= url('/login') ?>">Sign in</a>
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
    <div class="container d-flex flex-wrap justify-content-between py-3 gap-2">
        <span>© <?= date('Y') ?> OmniEats — TAR UMT AMCS2194 Mini Project</span>
        <span>Multi-Restaurant Food Ordering SaaS Platform</span>
    </div>
</footer>

<script src="<?= url('/assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
