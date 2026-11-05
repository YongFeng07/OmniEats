<?php
/**
 * Owner restaurant registration / profile form (Module 2).
 * Shared by create ($restaurant = null) and edit modes.
 */
$value = function (string $field, string $default = '') use ($restaurant) {
    $old = old($field);
    if ($old !== '') {
        return $old;
    }
    return $restaurant[$field] ?? $default;
};
?>
<div class="container py-4" style="max-width: 760px;">
    <h1 class="h4 mb-1"><?= e($heading) ?></h1>
    <p class="text-muted small mb-4"><?= e($subtext) ?></p>

    <?php if ($restaurant !== null): ?>
        <div class="alert alert-secondary small">
            Status: <strong><?= e(ucfirst($restaurant['status'])) ?></strong>
            <?php if ($restaurant['status'] === 'pending'): ?>
                — your restaurant is hidden from customers until a platform administrator approves it.
            <?php elseif ($restaurant['status'] === 'deactivated'): ?>
                — your restaurant is deactivated and hidden from customers. Contact the platform administrator.
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form method="post" action="<?= e($action) ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="name">Restaurant name</label>
                    <input type="text" class="form-control" id="name" name="name"
                           value="<?= e($value('name')) ?>" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="description">Description</label>
                    <textarea class="form-control" id="description" name="description"
                              rows="3"><?= e($value('description')) ?></textarea>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="phone">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone"
                               value="<?= e($value('phone')) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">Contact email <span class="text-muted">(optional)</span></label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?= e($value('email')) ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="address">Address</label>
                    <input type="text" class="form-control" id="address" name="address"
                           value="<?= e($value('address')) ?>" required>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label" for="city">City</label>
                        <input type="text" class="form-control" id="city" name="city"
                               value="<?= e($value('city')) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="state">State</label>
                        <input type="text" class="form-control" id="state" name="state"
                               value="<?= e($value('state')) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="postcode">Postcode</label>
                        <input type="text" class="form-control" id="postcode" name="postcode"
                               value="<?= e($value('postcode')) ?>" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="opening_hours">Opening hours</label>
                    <input type="text" class="form-control" id="opening_hours" name="opening_hours"
                           placeholder="e.g. Mon-Sun 10:00 - 22:00"
                           value="<?= e($value('opening_hours')) ?>" required>
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label" for="logo">Logo <span class="text-muted">(JPG/PNG/WebP, max 2 MB)</span></label>
                        <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                        <?php if (!empty($restaurant['logo'])): ?>
                            <div class="form-text">Current: <img src="<?= asset($restaurant['logo']) ?>"
                                 alt="current logo" style="height: 40px;"> leave empty to keep.</div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="cover_image">Cover image <span class="text-muted">(optional)</span></label>
                        <input type="file" class="form-control" id="cover_image" name="cover_image" accept="image/*">
                        <?php if (!empty($restaurant['cover_image'])): ?>
                            <div class="form-text">Current: <img src="<?= asset($restaurant['cover_image']) ?>"
                                 alt="current cover" style="height: 40px;"> leave empty to keep.</div>
                        <?php endif; ?>
                    </div>
                </div>
                <button type="submit" class="btn omnieats-btn">
                    <?= $restaurant === null ? 'Register restaurant' : 'Save changes' ?>
                </button>
            </form>
        </div>
    </div>
</div>
