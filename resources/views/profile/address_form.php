<?php
/**
 * Address edit form (Customer Profile module — Student 2).
 * Expects: $title, $action, $address.
 */
$value = function (string $field) use ($address) {
    $old = old($field);
    if ($old !== '') {
        return $old;
    }
    return $address[$field] ?? '';
};
?>
<div class="container py-4" style="max-width: 640px;">
    <h1 class="h4 mb-4"><?= e($title) ?></h1>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form method="post" action="<?= e($action) ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="label">Label</label>
                    <input type="text" class="form-control" id="label" name="label"
                           value="<?= e($value('label')) ?>" placeholder="e.g. Home" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="recipient_name">Recipient name</label>
                    <input type="text" class="form-control" id="recipient_name" name="recipient_name"
                           value="<?= e($value('recipient_name')) ?>" required>
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
                               value="<?= e($value('city')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="state">State</label>
                        <input type="text" class="form-control" id="state" name="state"
                               value="<?= e($value('state')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="postcode">Postcode</label>
                        <input type="text" class="form-control" id="postcode" name="postcode"
                               maxlength="5" value="<?= e($value('postcode')) ?>">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="phone">Phone</label>
                    <input type="tel" class="form-control" id="phone" name="phone"
                           value="<?= e($value('phone')) ?>" required>
                </div>
                <button type="submit" class="btn omnieats-btn">Save changes</button>
                <a class="btn btn-outline-secondary" href="<?= url('/profile') ?>">Cancel</a>
            </form>
        </div>
    </div>
</div>
