<?php
/**
 * Owner food item create/edit form (Module 3).
 * Shared by create ($item = null) and edit modes.
 */
$value = function (string $field, string $default = '') use ($item) {
    $old = old($field);
    if ($old !== '') {
        return $old;
    }
    return $item[$field] ?? $default;
};
$currentCategory = old('category_id') !== '' ? old('category_id') : (string) ($item['category_id'] ?? '');
?>
<div class="container py-4" style="max-width: 640px;">
    <h1 class="h4 mb-4"><?= e($heading) ?></h1>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form method="post" action="<?= e($action) ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="name">Food name</label>
                    <input type="text" class="form-control" id="name" name="name"
                           value="<?= e($value('name')) ?>" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="category_id">Category</label>
                    <select class="form-select" id="category_id" name="category_id" required>
                        <option value="">Choose a category…</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category['id'] ?>" <?= $currentCategory === (string) $category['id'] ? 'selected' : '' ?>>
                                <?= e($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="price">Price (RM)</label>
                    <input type="number" class="form-control" id="price" name="price"
                           min="0.01" step="0.01" value="<?= e($value('price')) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="description">Description</label>
                    <textarea class="form-control" id="description" name="description"
                              rows="3"><?= e($value('description')) ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="image">Photo <span class="text-muted">(JPG/PNG/WebP, max 2 MB)</span></label>
                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                    <?php if (!empty($item['image'])): ?>
                        <div class="form-text">Current: <img src="<?= asset($item['image']) ?>"
                             alt="current" style="height: 40px;"> leave empty to keep.</div>
                    <?php endif; ?>
                </div>
                <div class="mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_available" name="is_available"
                               value="1" <?= ($value('is_available', '1')) === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_available">
                            Available for ordering <span class="text-muted small">(availability is re-checked at checkout)</span>
                        </label>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn omnieats-btn">Save food item</button>
                    <a class="btn btn-outline-secondary" href="<?= url('/owner/foods') ?>">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
