<?php
/**
 * Home page (customer view) — Cream Soft UI:
 * category chips → promo banner → featured restaurant cards.
 * Categories link into the real restaurant search; the promo banner is
 * a static marketing element. No hardcoded restaurant data.
 */
$categories = [
    ['label' => 'Nasi Lemak', 'icon' => '🍛', 'q' => 'nasi'],
    ['label' => 'Fast Food',  'icon' => '🍔', 'q' => 'pizza'],
    ['label' => 'Drinks',     'icon' => '🥤', 'q' => 'drinks'],
    ['label' => 'Dessert',    'icon' => '🍰', 'q' => 'dessert'],
    ['label' => 'Pasta',      'icon' => '🍝', 'q' => 'pasta'],
    ['label' => 'Breakfast',  'icon' => '🥞', 'q' => 'roti'],
];
?>
<div class="container py-4">
    <!-- Categories row: round icons -->
    <section class="mb-4">
        <h2 class="h6 text-muted text-uppercase mb-3">Categories</h2>
        <div class="row row-cols-3 row-cols-md-6 g-3 text-center">
            <?php foreach ($categories as $category): ?>
                <div class="col">
                    <a class="category-chip" href="<?= url('/restaurants?q=' . urlencode($category['q'])) ?>">
                        <span class="category-chip-circle"><?= e($category['icon']) ?></span>
                        <span class="category-chip-label"><?= e($category['label']) ?></span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Voucher banner -->
    <section class="mb-5">
        <div class="promo-banner">
            <div>
                <h2 class="h4 mb-1">🎟️ Vouchers are waiting for you</h2>
                <p class="mb-0 small" style="opacity: .9;">Tap a voucher in your cart — up to RM 8 off. Smart validation applies it instantly.</p>
            </div>
            <a class="promo-cta" href="<?= url('/restaurants') ?>">Order now →</a>
        </div>
    </section>

    <!-- Merchant CTA (Grab/Foodpanda "partner with us" strip) -->
    <section class="mb-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h2 class="h5 mb-1">🍽️ Own a restaurant?</h2>
                    <p class="text-muted small mb-0">
                        Register your restaurant, upload your menu and start receiving orders —
                        your profile goes live after platform approval.
                    </p>
                </div>
                <a class="btn omnieats-btn" href="<?= url('/register/restaurant') ?>">Partner with us →</a>
            </div>
        </div>
    </section>

    <!-- Featured restaurants -->
    <section>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">Popular restaurants</h2>
            <a class="small text-decoration-none" href="<?= url('/restaurants') ?>">See all →</a>
        </div>
        <div class="row g-4">
            <?php foreach ($featured as $restaurant): ?>
                <?php view('shared/restaurant_card', ['restaurant' => $restaurant], null); ?>
            <?php endforeach; ?>

            <?php if ($featured === []): ?>
                <div class="col-12">
                    <div class="alert alert-info mb-0">
                        Restaurants appear here once they are approved and open.
                        <a class="alert-link" href="<?= url('/restaurants') ?>">Browse the listing</a>.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
