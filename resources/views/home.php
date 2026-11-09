<?php
/**
 * Home page (customer view) — Cream Soft UI:
 * REAL category chips (DB-distinct categories across approved
 * restaurants) → voucher banner → featured restaurant cards.
 * No hardcoded category data.
 */
$iconByKeyword = function (string $name): string {
    $name = strtolower($name);
    return match (true) {
        str_contains($name, 'drink'), str_contains($name, 'beverage'), str_contains($name, 'coffee'), str_contains($name, 'tea') => '🥤',
        str_contains($name, 'pizza')           => '🍕',
        str_contains($name, 'pasta')           => '🍝',
        str_contains($name, 'dessert'), str_contains($name, 'sweet') => '🍰',
        str_contains($name, 'nasi'), str_contains($name, 'rice')     => '🍛',
        str_contains($name, 'breakfast'), str_contains($name, 'roti') => '🥞',
        str_contains($name, 'burger')          => '🍔',
        str_contains($name, 'side'), str_contains($name, 'snack')    => '🍟',
        str_contains($name, 'main')            => '🍽️',
        default                                 => '🍴',
    };
};
?>
<div class="container py-4">
    <!-- Categories row: REAL database categories -->
    <section class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h6 text-muted text-uppercase mb-0">Categories</h2>
            <a class="small text-decoration-none" href="<?= url('/categories') ?>">All categories →</a>
        </div>
        <div class="row row-cols-3 row-cols-md-6 g-3 text-center">
            <?php foreach ($categories as $category): ?>
                <div class="col">
                    <a class="category-chip" href="<?= url('/categories/' . urlencode($category['name'])) ?>">
                        <span class="category-chip-circle"><?= e($iconByKeyword($category['name'])) ?></span>
                        <span class="category-chip-label"><?= e($category['name']) ?></span>
                        <span class="small text-muted d-block"><?= (int) $category['restaurant_count'] ?> restaurant(s)</span>
                    </a>
                </div>
            <?php endforeach; ?>
            <?php if ($categories === []): ?>
                <div class="col-12">
                    <p class="text-muted small mb-0">Categories appear as restaurants join the platform.</p>
                </div>
            <?php endif; ?>
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
