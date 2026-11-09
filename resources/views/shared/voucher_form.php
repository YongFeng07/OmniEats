<?php
/**
 * Shared voucher form (Promotion & Voucher module — Student 1).
 * Used by both the admin (with a restaurant selector) and the owner
 * (tenant fixed by the session). Expects: $action, $voucher (or null),
 * and optionally $restaurants (admin only).
 */
$value = function (string $field, string $default = '') use ($voucher) {
    $old = old($field);
    if ($old !== '') {
        return $old;
    }
    if ($voucher !== null && isset($voucher[$field]) && $voucher[$field] !== null) {
        return (string) $voucher[$field];
    }
    return $default;
};
$dt = fn (?string $raw): string => $raw !== null && $raw !== ''
    ? date('Y-m-d\TH:i', strtotime($raw))
    : '';
?>
<div class="container py-4" style="max-width: 760px;">
    <h1 class="h4 mb-4"><?= e($title) ?></h1>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form method="post" action="<?= e($action) ?>">
                <?= csrf_field() ?>
                <div class="row g-3 mb-3">
                    <div class="col-md-5">
                        <label class="form-label" for="code">Voucher code</label>
                        <input type="text" class="form-control" id="code" name="code"
                               placeholder="e.g. WELCOME10" maxlength="30"
                               value="<?= e($value('code')) ?>" required>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label" for="title">Title</label>
                        <input type="text" class="form-control" id="title" name="title"
                               placeholder="e.g. Welcome 10% Off" maxlength="100"
                               value="<?= e($value('title')) ?>" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label" for="discount_type">Discount type</label>
                        <select class="form-select" id="discount_type" name="discount_type"
                                onchange="document.getElementById('max-discount-wrap').style.display = this.value === 'percent' ? '' : 'none';">
                            <option value="fixed" <?= $value('discount_type') === 'fixed' ? 'selected' : '' ?>>Fixed amount (RM)</option>
                            <option value="percent" <?= $value('discount_type') === 'percent' ? 'selected' : '' ?>>Percentage (%)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="discount_value">Discount value</label>
                        <input type="number" step="0.01" min="0" class="form-control"
                               id="discount_value" name="discount_value"
                               value="<?= e($value('discount_value')) ?>" required>
                    </div>
                    <div class="col-md-4" id="max-discount-wrap"
                         style="<?= $value('discount_type') !== 'percent' ? 'display: none;' : '' ?>">
                        <label class="form-label" for="max_discount">Max discount <span class="text-muted">(optional)</span></label>
                        <input type="number" step="0.01" min="0" class="form-control"
                               id="max_discount" name="max_discount"
                               value="<?= e($value('max_discount')) ?>">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label" for="min_order">Minimum order (RM)</label>
                        <input type="number" step="0.01" min="0" class="form-control"
                               id="min_order" name="min_order" value="<?= e($value('min_order', '0')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="usage_limit">Usage limit</label>
                        <input type="number" min="1" class="form-control" id="usage_limit"
                               name="usage_limit" value="<?= e($value('usage_limit', '100')) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="restaurant_id">Restaurant
                            <?php if (isset($restaurants)): ?>
                                <span class="text-muted">(all = platform-wide)</span>
                            <?php endif; ?>
                        </label>
                        <?php if (isset($restaurants)): ?>
                            <select class="form-select" id="restaurant_id" name="restaurant_id">
                                <option value="">All restaurants</option>
                                <?php foreach ($restaurants as $restaurant): ?>
                                    <option value="<?= (int) $restaurant['id'] ?>"
                                        <?= (int) $value('restaurant_id') === (int) $restaurant['id'] ? 'selected' : '' ?>>
                                        <?= e($restaurant['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="text" class="form-control" value="Your restaurant" disabled>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label" for="starts_at">Start date &amp; time</label>
                        <input type="datetime-local" class="form-control" id="starts_at"
                               name="starts_at" value="<?= e($dt($value('starts_at'))) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="ends_at">Expiry date &amp; time</label>
                        <input type="datetime-local" class="form-control" id="ends_at"
                               name="ends_at" value="<?= e($dt($value('ends_at'))) ?>" required>
                    </div>
                </div>

                <?php if ($voucher !== null): ?>
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                            <?= (int) $value('is_active', '1') === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Voucher active</label>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn omnieats-btn">
                    <?= $voucher === null ? 'Create voucher' : 'Save changes' ?>
                </button>
                <a class="btn btn-outline-secondary"
                   href="<?= url($voucher === null ? (isset($restaurants) ? '/admin/vouchers' : '/owner/vouchers') : (isset($restaurants) ? '/admin/vouchers' : '/owner/vouchers')) ?>">Cancel</a>
            </form>
        </div>
    </div>
</div>
