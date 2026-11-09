<?php
/**
 * One category's browse page (Grab/Foodpanda-style): every approved
 * restaurant offering this category, with its items and add-to-cart.
 * Expects: $title, $name, $restaurants, $menus (by restaurant id).
 */
$viewer = auth_user();
$isCustomer = $viewer && in_array('customer', $viewer['roles'], true);
?>
<div class="container py-4" style="max-width: 960px;">
    <h1 class="h4 mb-1"><?= e($name) ?></h1>
    <p class="text-muted small mb-4">
        <?= count($restaurants) ?> restaurant(s) offer this category.
    </p>

    <?php foreach ($restaurants as $restaurant): ?>
        <?php $items = $menus[(int) $restaurant['id']] ?? []; ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <a class="text-decoration-none fw-bold text-dark"
                           href="<?= url('/restaurants/' . (int) $restaurant['id']) ?>">
                            <?= e($restaurant['name']) ?>
                        </a>
                        <p class="small text-muted mb-0">
                            <?= (int) $restaurant['is_open'] === 1
                                ? '<span class="text-success">● OPEN</span>'
                                : '<span class="text-danger">● CLOSED</span>' ?>
                            <?php if ($restaurant['rating'] !== null): ?>
                                · ⭐ <?= e(number_format((float) $restaurant['rating'], 1)) ?>
                            <?php endif; ?>
                            <?= $restaurant['delivery_minutes'] !== null ? '· 🛵 ' . (int) $restaurant['delivery_minutes'] . ' min' : '' ?>
                            <?php if ((int) $restaurant['free_delivery'] === 1): ?>
                                · <span class="text-info">🛵 Free Delivery</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <a class="btn btn-sm btn-outline-primary"
                       href="<?= url('/restaurants/' . (int) $restaurant['id']) ?>">Full menu →</a>
                </div>

                <div class="row g-3">
                    <?php foreach ($items as $item): ?>
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <?php if (!empty($item['image'])): ?>
                                    <img src="<?= asset($item['image']) ?>" class="card-img-top"
                                         style="height: 100px; object-fit: cover;" alt="<?= e($item['name']) ?>">
                                <?php else: ?>
                                    <div class="card-img-top d-flex align-items-center justify-content-center bg-light"
                                         style="height: 100px;"><span style="font-size: 1.8rem;">🍽️</span></div>
                                <?php endif; ?>
                                <div class="card-body p-2">
                                    <a class="small fw-semibold text-dark text-decoration-none"
                                       href="<?= url('/foods/' . (int) $item['id']) ?>"><?= e($item['name']) ?></a>
                                    <p class="small omnieats-price mb-0">
                                        RM <?= e(number_format((float) $item['price'], 2)) ?>
                                    </p>
                                    <?php if ($isCustomer && (int) $restaurant['is_open'] === 1): ?>
                                        <form method="post" action="<?= url('/cart/add') ?>" class="mt-1">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="food_item_id" value="<?= (int) $item['id'] ?>">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="btn btn-sm omnieats-btn w-100">Add +</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($items === []): ?>
                        <div class="col-12"><p class="text-muted small mb-0">No available items right now.</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
