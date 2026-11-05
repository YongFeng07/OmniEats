<?php
/**
 * Owner branch create/edit form (Module 2).
 * Shared by create ($branch = null) and edit modes.
 */
$value = function (string $field) use ($branch) {
    $old = old($field);
    if ($old !== '') {
        return $old;
    }
    return $branch[$field] ?? '';
};
?>
<div class="container py-4" style="max-width: 640px;">
    <h1 class="h4 mb-4"><?= e($heading) ?></h1>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form method="post" action="<?= e($action) ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="name">Branch name</label>
                    <input type="text" class="form-control" id="name" name="name"
                           value="<?= e($value('name')) ?>" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="address">Address <span class="text-muted">(optional)</span></label>
                    <input type="text" class="form-control" id="address" name="address"
                           value="<?= e($value('address')) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="phone">Phone <span class="text-muted">(optional)</span></label>
                    <input type="tel" class="form-control" id="phone" name="phone"
                           value="<?= e($value('phone')) ?>">
                </div>
                <div class="mb-4">
                    <label class="form-label" for="opening_hours">Opening hours <span class="text-muted">(optional)</span></label>
                    <input type="text" class="form-control" id="opening_hours" name="opening_hours"
                           placeholder="e.g. Mon-Sun 10:00 - 22:00"
                           value="<?= e($value('opening_hours')) ?>">
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn omnieats-btn">Save branch</button>
                    <a class="btn btn-outline-secondary" href="<?= url('/owner/branches') ?>">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
