<?php
/**
 * Shopping cart (Cream Soft UI + AJAX).
 * Flexbox list — no <table>. The [+]/[−] pill buttons and Remove use
 * fetch() against the JSON-aware endpoints, so the page never reloads:
 * quantities, line totals, the order summary and the navbar badge all
 * update in place. A promo code field sits at the bottom of the
 * summary card (demo code: OMNIEATS5 = RM 5 off).
 */
$cart = $contents['cart'];
$restaurant = $contents['restaurant'];
$items = $contents['items'];
$total = max(0, $contents['subtotal'] - $discount);
?>
<div class="container py-4" style="max-width: 960px;">
    <h1 class="h3 mb-3">My Cart</h1>
    <input type="hidden" id="csrf-token" value="<?= e(csrf_token()) ?>">

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
                <div class="d-flex flex-column gap-3" id="cart-items">
                    <?php foreach ($items as $line): ?>
                        <div class="cart-item" data-item="<?= (int) $line['food_item_id'] ?>"
                             data-price="<?= e(number_format((float) $line['unit_price'], 2, '.', '')) ?>">
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
                                <p class="fw-bold mb-2 text-dark" data-line-subtotal>
                                    RM <?= e(number_format((float) $line['unit_price'] * (int) $line['quantity'], 2)) ?>
                                </p>
                                <div class="qty-pill">
                                    <button type="button" class="qty-btn qty-dec" aria-label="Decrease quantity"
                                        <?= (int) $line['quantity'] <= 1 ? 'disabled' : '' ?>>−</button>
                                    <span class="qty-value" data-qty><?= (int) $line['quantity'] ?></span>
                                    <button type="button" class="qty-btn qty-inc" aria-label="Increase quantity">+</button>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger mt-2 btn-remove">Remove</button>
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
                        <strong class="text-dark" data-subtotal>RM <?= e(number_format($contents['subtotal'], 2)) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1 text-success" id="promo-row"
                         style="<?= $discount > 0 ? '' : 'display: none;' ?>">
                        <span>Promo (<?= e($promoCode) ?>)</span>
                        <span data-discount>− RM <?= e(number_format($discount, 2)) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 small text-muted">
                        <span>Delivery fee</span>
                        <span>RM <?= e(number_format($deliveryFee, 2)) ?> for delivery</span>
                    </div>
                    <div class="d-flex justify-content-between fs-5 fw-bold text-dark mb-3">
                        <span>Total</span><span data-total>RM <?= e(number_format($total, 2)) ?></span>
                    </div>

                    <!-- Promo code -->
                    <div class="input-group mb-1">
                        <input type="text" id="promo-input" class="form-control"
                               placeholder="Promo code" aria-label="Promo code">
                        <button class="btn btn-primary" type="button" id="promo-apply">Apply</button>
                    </div>
                    <p class="small mb-3" id="promo-message" style="min-height: 1.2rem;"></p>

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

<script>
    /**
     * Vanilla JS AJAX cart — no page reloads.
     * The endpoints accept normal form posts too, so the cart still
     * works with JavaScript disabled.
     */
    (() => {
        const csrf = document.getElementById('csrf-token')?.value ?? '';
        const updateUrl = <?= json_encode(url('/cart/update')) ?>;
        const removeUrl = <?= json_encode(url('/cart/remove')) ?>;
        const promoUrl  = <?= json_encode(url('/cart/promo')) ?>;

        const money = v => 'RM ' + Number(v).toFixed(2);

        function updateBadge(count) {
            if (count === undefined) return;
            const badge = document.querySelector('.navbar [data-cart-count]');
            if (badge) {
                badge.textContent = count > 0 ? count : '';
                badge.style.display = count > 0 ? '' : 'none';
            }
        }

        function applySummary(data) {
            document.querySelector('[data-subtotal]').textContent = money(data.subtotal);
            const totalEl = document.querySelector('[data-total]');
            if (totalEl) totalEl.textContent = money(data.total);

            const discountRow = document.getElementById('promo-row');
            if (Number(data.discount) > 0) {
                const el = document.querySelector('[data-discount]');
                if (el) el.textContent = '− RM ' + Number(data.discount).toFixed(2);
                if (discountRow) discountRow.style.display = '';
            } else if (discountRow) {
                discountRow.style.display = 'none';
            }

            updateBadge(data.count);
        }

        async function post(url, body) {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'fetch',
                },
                body: new URLSearchParams({ _token: csrf, ...body }),
            });
            return res.json();
        }

        // [+]/[−] quantity buttons
        document.querySelectorAll('.cart-item').forEach(row => {
            const foodId = row.dataset.item;
            const qtyEl = row.querySelector('[data-qty]');
            const lineEl = row.querySelector('[data-line-subtotal]');
            const decBtn = row.querySelector('.qty-dec');
            const incBtn = row.querySelector('.qty-inc');

            const changeQty = async (newQty) => {
                decBtn.disabled = incBtn.disabled = true;
                try {
                    const data = await post(updateUrl, { food_item_id: foodId, quantity: newQty });
                    if (data.ok) {
                        qtyEl.textContent = data.quantity;
                        if (lineEl) lineEl.textContent = money(data.line_subtotal);
                        decBtn.disabled = data.quantity <= 1;
                        applySummary(data);
                    }
                } catch (e) {
                    /* network hiccup — buttons re-enable, user retries */
                }
                decBtn.disabled = decBtn.disabled || Number(qtyEl.textContent) <= 1;
                incBtn.disabled = false;
            };

            incBtn.addEventListener('click', () => changeQty(Number(qtyEl.textContent) + 1));
            decBtn.addEventListener('click', () => changeQty(Number(qtyEl.textContent) - 1));

            row.querySelector('.btn-remove')?.addEventListener('click', async () => {
                const data = await post(removeUrl, { food_item_id: foodId });
                if (data.ok) {
                    row.remove();
                    applySummary(data);
                    if (data.count === 0) location.reload(); // show the empty state
                }
            });
        });

        // Promo code
        document.getElementById('promo-apply')?.addEventListener('click', async () => {
            const input = document.getElementById('promo-input');
            const msg = document.getElementById('promo-message');
            const data = await post(promoUrl, { code: input.value });
            if (data.ok) {
                msg.innerHTML = '<span class="text-success">' + data.message + '</span>';
                applySummary({ subtotal: data.subtotal, total: data.total, discount: data.discount, count: undefined });
            } else {
                msg.innerHTML = '<span class="text-danger">' + data.message + '</span>';
            }
        });
    })();
</script>
