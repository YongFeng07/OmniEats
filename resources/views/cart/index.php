<?php
/**
 * Shopping cart (Module 5). One restaurant per cart; totals computed
 * on the backend by CartService.
 */
$cart = $contents['cart'];
$restaurant = $contents['restaurant'];
$items = $contents['items'];
?>
<div class="container py-4" style="max-width: 900px;">
    <h1 class="h4 mb-3">My Cart</h1>

    <?php if ($cart === null || $items === []): ?>
        <div class="alert alert-info">
            Your cart is empty.
            <a class="alert-link" href="<?= url('/restaurants') ?>">Browse restaurants</a> to add some food.
        </div>
    <?php else: ?>
        <p class="text-muted small mb-3">
            Ordering from <strong><?= e($restaurant['name']) ?></strong>
            <?php if ((int) $restaurant['is_open'] !== 1): ?>
                <span class="badge text-bg-danger">CLOSED</span>
            <?php endif; ?>
        </p>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th style="width: 110px;">Price</th>
                                    <th style="width: 160px;">Quantity</th>
                                    <th style="width: 100px;">Subtotal</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $line): ?>
                                    <tr>
                                        <td>
                                            <span class="fw-semibold"><?= e($line['food_name']) ?></span>
                                            <?php if ((int) $line['is_available'] !== 1): ?>
                                                <span class="badge text-bg-secondary">Unavailable</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>RM <?= e(number_format((float) $line['unit_price'], 2)) ?></td>
                                        <td>
                                            <form method="post" action="<?= url('/cart/update') ?>" class="d-flex gap-1">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="food_item_id" value="<?= (int) $line['food_item_id'] ?>">
                                                <input type="number" class="form-control form-control-sm" name="quantity"
                                                       min="1" value="<?= (int) $line['quantity'] ?>" style="width: 70px;">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">Update</button>
                                            </form>
                                        </td>
                                        <td>RM <?= e(number_format((float) $line['unit_price'] * (int) $line['quantity'], 2)) ?></td>
                                        <td>
                                            <form method="post" action="<?= url('/cart/remove') ?>">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="food_item_id" value="<?= (int) $line['food_item_id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">✕</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <form method="post" action="<?= url('/cart/clear') ?>" class="mt-3"
                      onsubmit="return confirm('Clear your entire cart?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Clear cart</button>
                </form>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h6 text-muted text-uppercase mb-3">Order summary</h2>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Subtotal</span>
                            <span>RM <?= e(number_format($contents['subtotal'], 2)) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Delivery fee</span>
                            <span>Calculated at checkout (RM <?= e(number_format($deliveryFee, 2)) ?> for delivery)</span>
                        </div>
                        <a class="btn omnieats-btn w-100 <?= (int) $restaurant['is_open'] !== 1 ? 'disabled' : '' ?>"
                           href="<?= url('/checkout') ?>">Proceed to checkout</a>
                        <?php if ((int) $restaurant['is_open'] !== 1): ?>
                            <p class="text-danger small mt-2 mb-0">This restaurant is closed — checkout is unavailable.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
