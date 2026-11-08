<?php
/**
 * Restaurant card (Cream Soft UI) — reusable partial.
 * Expects: $restaurant (row from restaurants table).
 *
 * The card uses the restaurant's own cover/logo image when uploaded;
 * otherwise it falls back to a food photo from Unsplash (deterministic
 * per restaurant id, so the same card always shows the same image).
 *
 * Rating, delivery time and the free-delivery badge come from REAL
 * restaurant columns (rating / delivery_minutes / free_delivery) — no
 * computed placeholder data. rating is NULL until the platform has real
 * ratings; the card then shows "New" instead of a star value.
 */
$isOpen = (int) $restaurant['is_open'] === 1;

$unsplash = [
    'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=600&q=80', // plated meal
    'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=600&q=80', // salad bowl
    'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=600&q=80', // pizza
    'https://images.unsplash.com/photo-1565958011703-44f9829ba187?w=600&q=80', // dessert
    'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&q=80', // bowl
    'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=600&q=80', // pancakes
];
$image = !empty($restaurant['cover_image'])
    ? asset($restaurant['cover_image'])
    : (!empty($restaurant['logo'])
        ? asset($restaurant['logo'])
        : $unsplash[(int) $restaurant['id'] % count($unsplash)]);

$rating = $restaurant['rating'] !== null ? number_format((float) $restaurant['rating'], 1) : null;
$minutes = $restaurant['delivery_minutes'] !== null ? (int) $restaurant['delivery_minutes'] : null;
$freeDelivery = (int) $restaurant['free_delivery'] === 1;
?>
<div class="col-md-6 col-lg-4">
    <a class="card restaurant-card card-hover h-100 text-decoration-none"
       href="<?= url('/restaurants/' . (int) $restaurant['id']) ?>">
        <div class="restaurant-card-img">
            <img src="<?= e($image) ?>" alt="<?= e($restaurant['name']) ?>" loading="lazy">
            <span class="restaurant-flag <?= $isOpen ? 'open' : 'closed' ?>">
                <?= $isOpen ? 'Open' : 'Closed' ?>
            </span>
            <?php if ($freeDelivery): ?>
                <span class="restaurant-flag free">🛵 Free Delivery</span>
            <?php endif; ?>
        </div>
        <div class="card-body p-4">
            <h2 class="h5 mb-1 text-dark"><?= e($restaurant['name']) ?></h2>
            <p class="small mb-2" style="color: var(--cream-muted);">
                <?php if ($rating !== null): ?>
                    ⭐ <?= e($rating) ?>
                <?php else: ?>
                    <span class="badge text-bg-warning">New</span>
                <?php endif; ?>
                <?php if ($minutes !== null): ?>
                    &nbsp;·&nbsp; 🛵 <?= $minutes ?> min
                <?php endif; ?>
            </p>
            <p class="small mb-0 text-muted">
                <?= e($restaurant['city']) ?> · <?= e($restaurant['opening_hours']) ?>
            </p>
        </div>
    </a>
</div>
