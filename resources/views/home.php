<?php
/**
 * Public landing page. The live restaurant listing replaces this
 * teaser section in Module 2 (Phase 5).
 */
?>
<section class="hero py-5">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="badge text-bg-light border mb-3">Multi-Restaurant Food Ordering SaaS</span>
                <h1 class="display-5 fw-bold mb-3">Order from your favourite restaurants, all in one place.</h1>
                <p class="lead text-secondary mb-4">
                    OmniEats lets independent restaurants run their own online ordering —
                    menus, orders, payments and delivery — on one shared platform.
                </p>
                <a class="btn omnieats-btn btn-lg px-4" href="<?= url('/login') ?>">Sign in to get started</a>
            </div>
            <div class="col-lg-6">
                <div class="hero-panel rounded-4 p-4 p-lg-5">
                    <?php if ($featured !== []): ?>
                        <p class="fw-semibold mb-3">Featured right now</p>
                        <?php foreach ($featured as $restaurant): ?>
                            <a class="text-decoration-none text-dark" href="<?= url('/restaurants/' . (int) $restaurant['id']) ?>">
                                <p class="mb-1 fw-semibold"><?= e($restaurant['name']) ?></p>
                            </a>
                            <p class="text-secondary small mb-3">
                                <?= e($restaurant['city']) ?> · <?= e($restaurant['opening_hours']) ?> ·
                                <span class="text-success">OPEN</span>
                            </p>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="mb-2">Restaurants appear here once they are approved and open.</p>
                        <p class="text-secondary small mb-0">Explore the platform as a customer or register your own restaurant.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <h2 class="h4 fw-bold text-center mb-4">How OmniEats works</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h3 class="h5">1. Browse restaurants</h3>
                        <p class="text-secondary mb-0">Discover registered restaurants, their menus, prices and photos.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h3 class="h5">2. Place your order</h3>
                        <p class="text-secondary mb-0">Add food to your cart, choose delivery or self-pickup, and pay.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h3 class="h5">3. Track live status</h3>
                        <p class="text-secondary mb-0">Follow your order from confirmation to ready, step by step.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
