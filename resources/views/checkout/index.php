<?php
/**
 * Checkout (Cream Soft UI): the form is split into smaller white
 * cards — fulfilment choice, optional branch/note, and a sticky order
 * summary. All logic (fee calculation, JS toggles, endpoints) is
 * unchanged — presentation only.
 */
$restaurant = $contents['restaurant'];
$items = $contents['items'];
$subtotal = $contents['subtotal'];
?>
<div class="container py-4" style="max-width: 960px;">
    <h1 class="h3 mb-4">Checkout — <?= e($restaurant['name']) ?></h1>

    <form method="post" action="<?= url('/checkout') ?>">
        <?= csrf_field() ?>
        <div class="row g-4">
            <div class="col-lg-7 d-flex flex-column gap-4">
                <div class="card p-4">
                    <h2 class="h6 text-muted text-uppercase mb-3">How do you want your order?</h2>
                    <div class="d-flex flex-column gap-2">
                        <label class="d-flex align-items-center gap-3 p-3 rounded-4" style="border: 1.5px solid var(--cream-line); cursor: pointer;">
                            <input class="form-check-input m-0" type="radio" name="fulfilment_type"
                                   value="pickup" checked onchange="syncFulfilment()">
                            <span>
                                <span class="fw-bold text-dark d-block">🏪 Self-pickup</span>
                                <span class="small text-muted">Collect it yourself — no delivery fee</span>
                            </span>
                        </label>
                        <label class="d-flex align-items-center gap-3 p-3 rounded-4" style="border: 1.5px solid var(--cream-line); cursor: pointer;">
                            <input class="form-check-input m-0" type="radio" name="fulfilment_type"
                                   value="delivery" onchange="syncFulfilment()">
                            <span>
                                <span class="fw-bold text-dark d-block">🛵 Delivery</span>
                                <span class="small text-muted">RM <?= e(number_format($deliveryFee, 2)) ?> delivery fee</span>
                            </span>
                        </label>
                    </div>

                    <div id="delivery-fields" style="display: none;">
                        <div class="mb-3 mt-2">
                            <label class="form-label" for="delivery_address">Delivery address</label>
                            <textarea class="form-control" id="delivery_address" name="delivery_address" rows="2"></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label" for="delivery_phone">Delivery phone</label>
                            <input type="tel" class="form-control" id="delivery_phone" name="delivery_phone">
                        </div>
                    </div>
                </div>

                <div class="card p-4">
                    <h2 class="h6 text-muted text-uppercase mb-3">Details</h2>
                    <div class="mb-3">
                        <label class="form-label" for="branch_id">Branch <span class="text-muted fw-normal">(optional)</span></label>
                        <select class="form-select" id="branch_id" name="branch_id">
                            <option value="">Any branch</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?= (int) $branch['id'] ?>"><?= e($branch['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="note">Note for the restaurant <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="text" class="form-control" id="note" name="note" maxlength="255">
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-lg fw-bold">Place order</button>
                    <a class="btn btn-outline-secondary btn-lg" href="<?= url('/cart') ?>">Back to cart</a>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card order-summary p-4">
                    <h2 class="h6 text-muted text-uppercase mb-3">Your order</h2>
                    <?php foreach ($items as $line): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span><?= (int) $line['quantity'] ?> × <?= e($line['food_name']) ?></span>
                            <strong class="text-dark">RM <?= e(number_format((float) $line['unit_price'] * (int) $line['quantity'], 2)) ?></strong>
                        </div>
                    <?php endforeach; ?>
                    <hr>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Subtotal</span><strong class="text-dark">RM <?= e(number_format($subtotal, 2)) ?></strong>
                    </div>
                    <?php if ($discount > 0): ?>
                        <div class="d-flex justify-content-between mb-1 text-success">
                            <span>Promo (<?= e($promoCode) ?>)</span>
                            <span>− RM <?= e(number_format($discount, 2)) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Delivery fee</span><span id="fee-label">RM 0.00</span>
                    </div>
                    <div class="d-flex justify-content-between fs-5 fw-bold text-dark">
                        <span>Total</span>
                        <span id="total-label">RM <?= e(number_format(max(0, $subtotal - $discount), 2)) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    // Delivery fields and live totals follow the fulfilment type.
    const FEE = <?= (float) $deliveryFee ?>;
    const SUBTOTAL = <?= (float) $subtotal ?>;
    const DISCOUNT = <?= (float) $discount ?>;
    function syncFulfilment() {
        const isDelivery = document.querySelector('input[name="fulfilment_type"][value="delivery"]').checked;
        document.getElementById('delivery-fields').style.display = isDelivery ? '' : 'none';
        document.getElementById('fee-label').textContent = 'RM ' + (isDelivery ? FEE : 0).toFixed(2);
        const total = Math.max(0, SUBTOTAL - DISCOUNT + (isDelivery ? FEE : 0));
        document.getElementById('total-label').textContent = 'RM ' + total.toFixed(2);
    }
    syncFulfilment();
</script>
