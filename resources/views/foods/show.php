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
                    <p class="h5 omnieats-price mb-3">RM <?= e(number_format((float) $item['price'], 2)) ?></p>

                    <p><?= e($item['description'] ?: 'No description available.') ?></p>

                    <div class="mb-3">
                        <?php if (!$isAvailable): ?>
                            <span class="badge text-bg-secondary">Unavailable right now</span>
                        <?php elseif (!$isOpen): ?>
                            <span class="badge text-bg-danger">Restaurant is CLOSED</span>
                        <?php else: ?>
                            <span class="badge text-bg-success">Available</span>
                        <?php endif; ?>
                    </div>

                    <a class="btn omnieats-btn"
                       href="<?= url('/restaurants/' . (int) $restaurant['id']) ?>">Back to menu</a>

                    <?php
                    $viewer = auth_user();
                    $isCustomer = $viewer && in_array('customer', $viewer['roles'], true);
                    ?>
                    <?php if ($isCustomer && $isAvailable && $isOpen): ?>
                        <form method="post" action="<?= url('/cart/add') ?>" class="d-flex gap-1 mt-3">
                            <?= csrf_field() ?>
                            <input type="hidden" name="food_item_id" value="<?= (int) $item['id'] ?>">
                            <input type="number" name="quantity" value="1" min="1"
                                   class="form-control form-control-sm" style="width: 80px;" aria-label="Quantity">
                            <button type="submit" class="btn btn-sm omnieats-btn">Add to cart</button>
                        </form>
                    <?php elseif (!$isCustomer): ?>
                        <a class="btn btn-outline-primary mt-3" href="<?= url('/login') ?>">Sign in to order</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
