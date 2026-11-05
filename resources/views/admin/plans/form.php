<?php
/**
 * Admin plan create/edit form (Module 4).
 * Shared by create ($plan = null) and edit modes.
 */
$value = function (string $field, string $default = '') use ($plan) {
    $old = old($field);
    if ($old !== '') {
        return $old;
    }
    return $plan[$field] ?? $default;
};
$itemsLimit = old('max_food_items') !== '' ? old('max_food_items') : ($plan['max_food_items'] ?? '');
?>
<div class="container py-4" style="max-width: 640px;">
    <h1 class="h4 mb-4"><?= e($heading) ?></h1>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form method="post" action="<?= e($action) ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="name">Plan name</label>
                    <input type="text" class="form-control" id="name" name="name"
                           value="<?= e($value('name')) ?>" required autofocus>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label" for="price">Price / month (RM)</label>
                        <input type="number" class="form-control" id="price" name="price"
                               min="0" step="0.01" value="<?= e($value('price', '0.00')) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="max_branches">Max branches</label>
                        <input type="number" class="form-control" id="max_branches" name="max_branches"
                               min="1" value="<?= e($value('max_branches')) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="max_food_items">Max food items</label>
                        <input type="number" class="form-control" id="max_food_items" name="max_food_items"
                               min="1" value="<?= e($itemsLimit) ?>">
                        <div class="form-text">Leave empty for unlimited.</div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="description">Description</label>
                    <textarea class="form-control" id="description" name="description"
                              rows="2"><?= e($value('description')) ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="features">Features <span class="text-muted">(one per line)</span></label>
                    <textarea class="form-control" id="features" name="features"
                              rows="4"><?= e($value('features')) ?></textarea>
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-8">
                        <label class="form-label" for="sort_order">Sort order</label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order"
                               min="0" max="999" value="<?= e($value('sort_order', '0')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label d-block">Status</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" id="is_active_1" name="is_active"
                                   value="1" <?= $value('is_active', '1') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active_1">Active</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" id="is_active_0" name="is_active"
                                   value="0" <?= $value('is_active', '1') === '0' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active_0">Archived</label>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn omnieats-btn">Save plan</button>
                    <a class="btn btn-outline-secondary" href="<?= url('/admin/plans') ?>">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
