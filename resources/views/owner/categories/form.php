<?php
/**
 * Owner category create/edit form (Module 3).
 * Shared by create ($category = null) and edit modes.
 */
$value = function (string $field, string $default = '') use ($category) {
    $old = old($field);
    if ($old !== '') {
        return $old;
    }
    return $category[$field] ?? $default;
};
?>
<div class="container py-4" style="max-width: 560px;">
    <h1 class="h4 mb-4"><?= e($heading) ?></h1>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form method="post" action="<?= e($action) ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="name">Category name</label>
                    <input type="text" class="form-control" id="name" name="name"
                           list="platform-categories"
                           value="<?= e($value('name')) ?>" required autofocus>
                    <datalist id="platform-categories">
                        <?php foreach ($suggestions as $suggestion): ?>
                            <option value="<?= e($suggestion['name']) ?>">
                                Used by <?= (int) $suggestion['restaurant_count'] ?> restaurant(s)
                            </option>
                        <?php endforeach; ?>
                    </datalist>
                    <div class="form-text">
                        Pick an existing platform category (so customers find your items when
                        browsing categories) or type a new one.
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="description">Description <span class="text-muted">(optional)</span></label>
                    <input type="text" class="form-control" id="description" name="description"
                           value="<?= e($value('description')) ?>">
                </div>
                <div class="mb-4">
                    <label class="form-label" for="sort_order">Sort order <span class="text-muted">(0 shows first)</span></label>
                    <input type="number" class="form-control" id="sort_order" name="sort_order"
                           min="0" max="999" value="<?= e($value('sort_order', '0')) ?>">
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn omnieats-btn">Save category</button>
                    <a class="btn btn-outline-secondary" href="<?= url('/owner/categories') ?>">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
