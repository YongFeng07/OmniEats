<?php
/**
 * Own profile page (Module 1): edit name/email/phone, view account
 * summary, link to change password.
 */
$roles = $user['roles'] ?? [];
?>
<div class="container py-4" style="max-width: 900px;">
    <h1 class="h4 mb-4">My Profile</h1>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h6 text-muted text-uppercase mb-3">Account summary</h2>
                    <p class="mb-1"><strong><?= e($user['name']) ?></strong></p>
                    <p class="mb-3"><?= e($user['email']) ?></p>
                    <p class="mb-1">
                        Roles:
                        <?php foreach ($roles as $role): ?>
                            <span class="badge text-bg-secondary"><?= e(str_replace('_', ' ', $role)) ?></span>
                        <?php endforeach; ?>
                    </p>
                    <?php if (!empty($user['restaurant_id'])): ?>
                        <p class="mb-1">Restaurant ID: <?= (int) $user['restaurant_id'] ?></p>
                    <?php endif; ?>
                    <p class="text-muted small mb-0">Member since <?= e(date('d M Y', strtotime($user['created_at']))) ?></p>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h6 text-muted text-uppercase mb-3">Profile details</h2>
                    <form method="post" action="<?= url('/profile') ?>">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label" for="name">Full name</label>
                            <input type="text" class="form-control" id="name" name="name"
                                   value="<?= e(old('name', $user['name'])) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?= e(old('email', $user['email'])) ?>" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label" for="phone">Phone <span class="text-muted">(optional)</span></label>
                            <input type="tel" class="form-control" id="phone" name="phone"
                                   value="<?= e(old('phone', $user['phone'] ?? '')) ?>">
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <button type="submit" class="btn omnieats-btn">Save changes</button>
                            <a class="btn btn-outline-secondary" href="<?= url('/profile/password') ?>">Change password</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php $isCustomer = in_array('customer', $user['roles'], true); ?>
    <?php if ($isCustomer): ?>
        <div class="card shadow-sm mt-4">
            <div class="card-body p-4">
                <h2 class="h6 text-muted text-uppercase mb-1">📍 Saved addresses</h2>
                <p class="text-muted small mb-3">Used by the checkout address picker for delivery orders.</p>

                <div class="d-flex flex-column gap-2 mb-4">
                    <?php foreach ($addresses as $address): ?>
                        <div class="d-flex flex-wrap align-items-center gap-2 p-3 rounded-4"
                             style="border: 1.5px solid var(--cream-line);">
                            <div class="flex-grow-1">
                                <p class="mb-0 fw-semibold text-dark">
                                    <?= e($address['label']) ?>
                                    <?php if ((int) $address['is_default'] === 1): ?>
                                        <span class="badge text-bg-primary">Default</span>
                                    <?php endif; ?>
                                </p>
                                <p class="small text-muted mb-0">
                                    <?= e($address['recipient_name']) ?> · <?= e($address['address']) ?>,
                                    <?= e($address['postcode'] ?? '') ?> <?= e($address['city'] ?? '') ?>,
                                    <?= e($address['state'] ?? '') ?> · 📞 <?= e($address['phone']) ?>
                                </p>
                            </div>
                            <div class="d-flex gap-1">
                                <a class="btn btn-sm btn-outline-primary"
                                   href="<?= url('/profile/addresses/' . (int) $address['id'] . '/edit') ?>">Edit</a>
                                <?php if ((int) $address['is_default'] !== 1): ?>
                                    <form method="post" action="<?= url('/profile/addresses/' . (int) $address['id'] . '/default') ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Make default</button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" action="<?= url('/profile/addresses/' . (int) $address['id'] . '/delete') ?>"
                                      onsubmit="return confirm('Delete this address?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($addresses === []): ?>
                        <p class="text-muted small mb-0">No saved addresses yet — add one below.</p>
                    <?php endif; ?>
                </div>

                <form method="post" action="<?= url('/profile/addresses') ?>" class="row g-3">
                    <?= csrf_field() ?>
                    <div class="col-md-3">
                        <label class="form-label" for="label">Label</label>
                        <input type="text" class="form-control" id="label" name="label"
                               placeholder="e.g. Home" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="recipient_name">Recipient name</label>
                        <input type="text" class="form-control" id="recipient_name" name="recipient_name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="address">Address</label>
                        <input type="text" class="form-control" id="address" name="address" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="city">City</label>
                        <input type="text" class="form-control" id="city" name="city">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="state">State</label>
                        <input type="text" class="form-control" id="state" name="state">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="postcode">Postcode</label>
                        <input type="text" class="form-control" id="postcode" name="postcode"
                               maxlength="5" placeholder="e.g. 47300">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="phone">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone" required>
                    </div>
                    <div class="col-12 d-flex align-items-center gap-3">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1">
                            <label class="form-check-label small" for="is_default">Default address</label>
                        </div>
                        <button type="submit" class="btn omnieats-btn btn-sm">Add</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>
