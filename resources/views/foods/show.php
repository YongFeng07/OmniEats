<?php
/**
 * Public food item detail (Module 3).
 */
$isAvailable = (int) $item['is_available'] === 1;
$isOpen = (int) $restaurant['is_open'] === 1;
?>
<div class="container py-4" style="max-width: 760px;">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url('/restaurants') ?>">Restaurants</a></li>
            <li class="breadcrumb-item">
                <a href="<?= url('/restaurants/' . (int) $restaurant['id']) ?>"><?= e($restaurant['name']) ?></a>
            </li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($item['name']) ?></li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm">
        <div class="row g-0">
            <div class="col-md-5">
                <?php if (!empty($item['image'])): ?>
                    <img src="<?= asset($item['image']) ?>" class="w-100 h-100 rounded-start"
                         style="object-fit: cover;" alt="<?= e($item['name']) ?>">
                <?php else: ?>
                    <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light rounded-start"
                         style="min-height: 240px;">
                        <span style="font-size: 4rem;">🍽️</span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-7">
                <div class="card-body p-4">
                    <h1 class="h4 mb-1"><?= e($item['name']) ?></h1>
                    <p class="text-muted small mb-2">
                        <?= e($category['name'] ?? '') ?> · <?= e($restaurant['name']) ?>
                    </p>
                    <p class="h3 omnieats-price mb-3">RM <?= e(number_format((float) $item['price'], 2)) ?></p>

                    <p class="mb-3"><?= e($item['description'] ?: 'No description available.') ?></p>

                    <div class="mb-3 d-flex flex-wrap gap-2">
                        <?php if (!$isAvailable): ?>
                            <span class="badge text-bg-secondary">Unavailable right now</span>
                        <?php elseif (!$isOpen): ?>
                            <span class="badge text-bg-danger">Restaurant is CLOSED</span>
                        <?php else: ?>
                            <span class="badge text-bg-success">Available</span>
                        <?php endif; ?>
                        <?php if ($restaurant['rating'] !== null): ?>
                            <span class="badge text-bg-warning">⭐ <?= e(number_format((float) $restaurant['rating'], 1)) ?></span>
                        <?php endif; ?>
                        <?php if ((int) $restaurant['free_delivery'] === 1): ?>
                            <span class="badge text-bg-info">🛵 Free Delivery</span>
                        <?php endif; ?>
                    </div>

                    <?php
                    $viewer = auth_user();
                    $isCustomer = $viewer && in_array('customer', $viewer['roles'], true);
                    ?>
                    <?php if ($isCustomer && $isAvailable && $isOpen): ?>
                        <form method="post" action="<?= url('/cart/add') ?>" class="d-flex gap-2 align-items-center">
                            <?= csrf_field() ?>
                            <input type="hidden" name="food_item_id" value="<?= (int) $item['id'] ?>">
                            <div class="qty-pill">
                                <button type="button" class="qty-btn" onclick="const v=document.getElementById('qty');v.value=Math.max(1,+v.value-1)">−</button>
                                <span class="qty-value"><input type="number" id="qty" name="quantity" value="1"
                                    min="1" class="border-0 text-center bg-transparent" style="width: 48px;"
                                    aria-label="Quantity"></span>
                                <button type="button" class="qty-btn" onclick="const v=document.getElementById('qty');v.value=+v.value+1">+</button>
                            </div>
                            <button type="submit" class="btn omnieats-btn flex-grow-1">🛒 Add to cart</button>
                        </form>
                    <?php elseif (!$isCustomer): ?>
                        <a class="btn btn-outline-primary" href="<?= url('/login') ?>">Sign in to order</a>
                    <?php endif; ?>

                    <a class="btn btn-link btn-sm ps-0 mt-2 text-decoration-none"
                       href="<?= url('/restaurants/' . (int) $restaurant['id']) ?>">← Back to full menu</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Restaurant mini-card (Grab-style: where this comes from) -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body p-4 d-flex flex-wrap align-items-center gap-3">
            <?php if (!empty($restaurant['logo'])): ?>
                <img src="<?= asset($restaurant['logo']) ?>" class="rounded-circle"
                     style="width: 56px; height: 56px; object-fit: cover;" alt="logo">
            <?php else: ?>
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle"
                      style="width: 56px; height: 56px; background: #FDF0E9; font-size: 1.6rem;">🍽️</span>
            <?php endif; ?>
            <div class="flex-grow-1">
                <a class="text-decoration-none fw-bold text-dark"
                   href="<?= url('/restaurants/' . (int) $restaurant['id']) ?>"><?= e($restaurant['name']) ?></a>
                <p class="small text-muted mb-0">
                    <?= e($restaurant['city'] ?? '') ?> ·
                    <?= $restaurant['delivery_minutes'] !== null ? '🛵 ' . (int) $restaurant['delivery_minutes'] . ' min' : '' ?>
                    <?= (int) $restaurant['is_open'] === 1
                        ? '<span class="text-success">· ● OPEN</span>'
                        : '<span class="text-danger">· ● CLOSED</span>' ?>
                </p>
            </div>
            <a class="btn btn-sm btn-outline-primary" href="<?= url('/restaurants/' . (int) $restaurant['id']) ?>">View menu</a>
        </div>
    </div>

    <!-- Related items (Grab-style "you may also like") -->
    <?php if ($related !== []): ?>
        <h2 class="h6 text-muted text-uppercase mt-4 mb-3">More from this restaurant</h2>
        <div class="row g-3">
            <?php foreach ($related as $line): ?>
                <div class="col-6 col-md-3">
                    <a class="card h-100 border-0 shadow-sm text-decoration-none card-hover"
                       href="<?= url('/foods/' . (int) $line['id']) ?>">
                        <?php if (!empty($line['image'])): ?>
                            <img src="<?= asset($line['image']) ?>" class="card-img-top"
                                 style="height: 90px; object-fit: cover;" alt="<?= e($line['name']) ?>">
                        <?php else: ?>
                            <div class="card-img-top d-flex align-items-center justify-content-center bg-light"
                                 style="height: 90px;"><span style="font-size: 1.8rem;">🍽️</span></div>
                        <?php endif; ?>
                        <div class="card-body p-2">
                            <p class="small fw-semibold text-dark mb-0 text-truncate"><?= e($line['name']) ?></p>
                            <p class="small omnieats-price mb-0">RM <?= e(number_format((float) $line['price'], 2)) ?></p>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
