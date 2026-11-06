<?php
/**
 * Shopping cart (Cream Soft UI). Flexbox item list — no <table>.
 * Each row: round food image · title + price · pill quantity selector
 * [-] 1 [+]. Server endpoints (/cart/update, /cart/remove, /cart/clear)
 * are unchanged — this is presentation only.
 */
$cart = $contents['cart'];
$restaurant = $contents['restaurant'];
$items = $contents['items'];
?>
<div class="container py-4" style="max-width: 960px;">
    <h1 class="h3 mb-3">My Cart</h1>

    <?php if ($cart === null || $items === []): ?>
        <div class="alert alert-info">
            Your cart is empty.
            <a class="alert-link" href="<?= url('/restaurants') ?>">Browse restaurants</a> to add some food.
        </div>
    <?php else: ?>
        <p class="text-muted small mb-4">
            Ordering from <strong class="text-dark"><?= e($restaurant['name']) ?></strong>
            <?php if ((int) $restaurant['is_open'] !== 1): ?>
                <span class="badge text-bg-danger">CLOSED</span>
            <?php endif; ?>
        </p>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($items as $line): ?>
                        <div class="cart-item">
                            <?php if (!empty($line['image'])): ?>
                                <img class="cart-item-img" src="<?= asset($line['image']) ?>"
                                     alt="<?= e($line['food_name']) ?>">
                            <?php else: ?>
                                <div class="cart-item-img-placeholder">🍽️</div>
                            <?php endif; ?>

                            <div class="flex-grow-1">
                                <p class="fw-bold mb-0 text-dark"><?= e($line['food_name']) ?></p>
                                <p class="small mb-0" style="color: var(--cream-muted);">
                                    RM <?= e(number_format((float) $line['unit_price'], 2)) ?> each
                                </p>
                                <?php if ((int) $line['is_available'] !== 1): ?>
                                    <span class="badge text-bg-secondary mt-1">Unavailable</span>
                                <?php endif; ?>
                            </div>

                            <div class="text-end">
                                <p class="fw-bold mb-2 text-dark">
                                    RM <?= e(number_format((float) $line['unit_price'] * (int) $line['quantity'], 2)) ?>
                                </p>
                                <div class="qty-pill">
                                    <form method="post" action="<?= url('/cart/update') ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="food_item_id" value="<?= (int) $line['food_item_id'] ?>">
                                        <input type="hidden" name="quantity" value="<?= (int) $line['quantity'] - 1 ?>">
                                        <button type="submit" class="qty-btn" aria-label="Decrease quantity"
                                            <?= (int) $line['quantity'] <= 1 ? 'disabled' : '' ?>>−</button>
                                    </form>
                                    <span class="qty-value"><?= (int) $line['quantity'] ?></span>
                                    <form method="post" action="<?= url('/cart/update') ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="food_item_id" value="<?= (int) $line['food_item_id'] ?>">
                                        <input type="hidden" name="quantity" value="<?= (int) $line['quantity'] + 1 ?>">
                                        <button type="submit" class="qty-btn" aria-label="Increase quantity">+</button>
                                    </form>
                                </div>
                                <form method="post" action="<?= url('/cart/remove') ?>" class="mt-2">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="food_item_id" value="<?= (int) $line['food_item_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <form method="post" action="<?= url('/cart/clear') ?>" class="mt-4"
                      onsubmit="return confirm('Clear your entire cart?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Clear cart</button>
                </form>
            </div>

            <div class="col-lg-4">
                <div class="card order-summary p-4">
                    <h2 class="h6 text-muted text-uppercase mb-3">Order summary</h2>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Subtotal</span>
                        <strong class="text-dark">RM <?= e(number_format($contents['subtotal'], 2)) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-4 small text-muted">
                        <span>Delivery fee</span>
                        <span>RM <?= e(number_format($deliveryFee, 2)) ?> for delivery</span>
                    </div>
                    <a class="btn btn-primary btn-lg w-100 fw-bold <?= (int) $restaurant['is_open'] !== 1 ? 'disabled' : '' ?>"
                       href="<?= url('/checkout') ?>">Proceed to checkout →</a>
                    <?php if ((int) $restaurant['is_open'] !== 1): ?>
                        <p class="text-danger small mt-2 mb-0">This restaurant is closed — checkout is unavailable.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
