<?php
/**
 * All platform categories (Grab/Foodpanda-style browse) — real data:
 * distinct category names across approved restaurants with live counts.
 * Expects: $title, $categories.
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
        default                                 => '🍴',
    };
};
?>
<div class="container py-4" style="max-width: 960px;">
    <h1 class="h4 mb-1">Browse categories</h1>
    <p class="text-muted small mb-4">
        Every category below comes from real restaurant menus — pick one to see
        all restaurants offering it.
    </p>

    <div class="row g-4">
        <?php foreach ($categories as $category): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <a class="card h-100 border-0 shadow-sm text-decoration-none card-hover"
                   href="<?= url('/categories/' . urlencode($category['name'])) ?>">
                    <div class="card-body p-3 text-center">
                        <span style="font-size: 2.2rem;"><?= e($iconByKeyword($category['name'])) ?></span>
                        <p class="fw-bold text-dark mb-0"><?= e($category['name']) ?></p>
                        <p class="small text-muted mb-0">
                            <?= (int) $category['restaurant_count'] ?> restaurant(s) ·
                            <?= (int) $category['item_count'] ?> item(s)
                        </p>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
        <?php if ($categories === []): ?>
            <div class="col-12">
                <div class="alert alert-info mb-0">Categories appear as restaurants join the platform.</div>
            </div>
        <?php endif; ?>
    </div>
</div>
