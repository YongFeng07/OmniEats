<?php
/**
 * Rider account form — create (admin) / edit vehicle (admin).
 * Expects: $title, $action, $rider (or null for create).
 */
$value = function (string $field, string $default = '') use ($rider) {
    $old = old($field);
    if ($old !== '') {
        return $old;
    }
    return $rider[$field] ?? $default;
};
?>
<div class="container py-4" style="max-width: 640px;">
    <h1 class="h4 mb-4"><?= e($title) ?></h1>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form method="post" action="<?= e($action) ?>">
                <?= csrf_field() ?>
                <?php if ($rider === null): ?>
                    <div class="mb-3">
                        <label class="form-label" for="name">Full name</label>
                        <input type="text" class="form-control" id="name" name="name"
                               value="<?= e($value('name')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?= e($value('email')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="phone">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone"
                               value="<?= e($value('phone')) ?>">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="password">Temporary password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="password_confirmation">Confirm password</label>
                            <input type="password" class="form-control" id="password_confirmation"
                                   name="password_confirmation" required>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label" for="vehicle_type">Vehicle type</label>
                        <input type="text" class="form-control" id="vehicle_type" name="vehicle_type"
                               placeholder="e.g. Motorcycle" value="<?= e($value('vehicle_type')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="vehicle_plate">Vehicle plate</label>
                        <input type="text" class="form-control" id="vehicle_plate" name="vehicle_plate"
                               placeholder="e.g. WXY 1234" value="<?= e($value('vehicle_plate')) ?>">
                    </div>
                </div>
                <button type="submit" class="btn omnieats-btn">
                    <?= $rider === null ? 'Create rider account' : 'Save changes' ?>
                </button>
                <a class="btn btn-outline-secondary" href="<?= url('/admin/riders') ?>">Cancel</a>
            </form>
        </div>
    </div>
</div>
