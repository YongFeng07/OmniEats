<?php
/**
 * Checkout (Module 5): order summary + fulfilment selection. The order
 * is created in pending_payment; payment itself is Module 6.
 */
$restaurant = $contents['restaurant'];
$items = $contents['items'];
$subtotal = $contents['subtotal'];
?>
<div class="container py-4" style="max-width: 900px;">
    <h1 class="h4 mb-3">Checkout — <?= e($restaurant['name']) ?></h1>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h2 class="h6 text-muted text-uppercase mb-3">Fulfilment</h2>
                    <form method="post" action="<?= url('/checkout') ?>">
                        <?= csrf_field() ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" id="type_pickup" name="fulfilment_type"
                                   value="pickup" checked onchange="syncFulfilment()">
                            <label class="form-check-label" for="type_pickup">Self-pickup <span class="text-muted small">(no delivery fee)</span></label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" id="type_delivery" name="fulfilment_type"
                                   value="delivery" onchange="syncFulfilment()">
                            <label class="form-check-label" for="type_delivery">Delivery <span class="text-muted small">(RM <?= e(number_format($deliveryFee, 2)) ?>)</span></label>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="branch_id">Branch <span class="text-muted">(optional)</span></label>
                            <select class="form-select" id="branch_id" name="branch_id">
                                <option value="">Any branch</option>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?= (int) $branch['id'] ?>"><?= e($branch['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="delivery-fields" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label" for="delivery_address">Delivery address</label>
                                <textarea class="form-control" id="delivery_address" name="delivery_address" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="delivery_phone">Delivery phone</label>
                                <input type="tel" class="form-control" id="delivery_phone" name="delivery_phone">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="note">Note for the restaurant <span class="text-muted">(optional)</span></label>
                            <input type="text" class="form-control" id="note" name="note" maxlength="255">
                        </div>

                        <button type="submit" class="btn omnieats-btn">Place order</button>
                        <a class="btn btn-outline-secondary" href="<?= url('/cart') ?>">Back to cart</a>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h6 text-muted text-uppercase mb-3">Your order</h2>
                    <?php foreach ($items as $line): ?>
                        <div class="d-flex justify-content-between mb-1">
                            <span><?= (int) $line['quantity'] ?> × <?= e($line['food_name']) ?></span>
                            <span>RM <?= e(number_format((float) $line['unit_price'] * (int) $line['quantity'], 2)) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <hr>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Subtotal</span><span>RM <?= e(number_format($subtotal, 2)) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Delivery fee</span><span id="fee-label">RM 0.00</span>
                    </div>
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Total</span><span id="total-label">RM <?= e(number_format($subtotal, 2)) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Delivery fields and live totals follow the fulfilment type.
    const FEE = <?= (float) $deliveryFee ?>;
    const SUBTOTAL = <?= (float) $subtotal ?>;
    function syncFulfilment() {
        const isDelivery = document.getElementById('type_delivery').checked;
        document.getElementById('delivery-fields').style.display = isDelivery ? '' : 'none';
        document.getElementById('fee-label').textContent = 'RM ' + (isDelivery ? FEE : 0).toFixed(2);
        document.getElementById('total-label').textContent = 'RM ' + (SUBTOTAL + (isDelivery ? FEE : 0)).toFixed(2);
    }
    syncFulfilment();
</script>
