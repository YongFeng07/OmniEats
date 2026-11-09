<?php
/**
 * Shopee-style profile page: avatar, account stats, my reviews, recent
 * activity, profile edit (with avatar upload), address book.
 *
 * Expects: $title, $user, $stats, $myReviews, $activity, $addresses.
 */
$roles = $user['roles'] ?? [];
$isCustomer = in_array('customer', $user['roles'], true);
?>
<div class="container py-4" style="max-width: 980px;">
    <!-- Profile header -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-center gap-4">
                <?php if (!empty($user['avatar'])): ?>
                    <img class="profile-avatar" src="<?= asset($user['avatar']) ?>" alt="avatar">
                <?php else: ?>
                    <span class="profile-avatar-fallback"><?= e(mb_substr($user['name'], 0, 1)) ?></span>
                <?php endif; ?>
                <div class="flex-grow-1">
                    <h1 class="h4 mb-1"><?= e($user['name']) ?></h1>
                    <p class="text-muted small mb-2">
                        <?= e($user['email']) ?>
                        <?php if (!empty($user['phone'])): ?> · 📞 <?= e($user['phone']) ?><?php endif; ?>
                    </p>
                    <div class="d-flex flex-wrap gap-1">
                        <?php foreach ($roles as $role): ?>
                            <span class="badge text-bg-secondary"><?= e(str_replace('_', ' ', $role)) ?></span>
                        <?php endforeach; ?>
                        <span class="badge text-bg-light border">
                            Member since <?= e(date('d M Y', strtotime($user['created_at']))) ?>
                        </span>
                    </div>
                </div>
                <a class="btn btn-outline-secondary" href="<?= url('/profile/password') ?>">Change password</a>
            </div>
        </div>
    </div>

    <!-- Account stats (Shopee-style) -->
    <div class="row g-3 mb-4">
        <?php $statItems = [
            'orders'     => ['📦', 'Orders'],
            'completed'  => ['✅', 'Completed'],
            'reviews'    => ['⭐', 'Reviews written'],
            'favourites' => ['❤️', 'Favourites'],
            'addresses'  => ['📍', 'Saved addresses'],
        ]; ?>
        <?php foreach ($statItems as $key => [$icon, $label]): ?>
            <div class="col-6 col-md-4 col-lg">
                <div class="stat-tile text-center h-100">
                    <p class="h3 stat-value mb-0"><?= (int) $stats[$key] ?></p>
                    <p class="text-muted small mb-0"><?= $icon ?> <?= e($label) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-4">
        <div class="col-lg-6 d-flex flex-column gap-4">
            <!-- Profile details + avatar upload -->
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h6 text-muted text-uppercase mb-3">Profile details</h2>
                    <form method="post" action="<?= url('/profile') ?>" enctype="multipart/form-data">
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
                        <div class="mb-3">
                            <label class="form-label" for="phone">Phone <span class="text-muted">(optional)</span></label>
                            <input type="tel" class="form-control" id="phone" name="phone"
                                   value="<?= e(old('phone', $user['phone'] ?? '')) ?>">
                        </div>
                        <div class="mb-4">
                            <label class="form-label" for="avatar">Profile picture
                                <span class="text-muted">(JPG/PNG/WebP, max 2 MB — leave empty to keep)</span></label>
                            <input type="file" class="form-control" id="avatar" name="avatar" accept="image/*">
                        </div>
                        <button type="submit" class="btn omnieats-btn">Save changes</button>
                    </form>
                </div>
            </div>

            <!-- My reviews -->
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h6 text-muted text-uppercase mb-3">My reviews</h2>
                    <?php foreach ($myReviews as $review): ?>
                        <div class="py-2 border-bottom">
                            <p class="mb-1">
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <?= $s <= (int) $review['rating'] ? '⭐' : '☆' ?>
                                <?php endfor; ?>
                                <a class="text-decoration-none fw-semibold text-dark ms-1"
                                   href="<?= url('/restaurants/' . (int) $review['restaurant_id']) ?>">
                                    <?= e($review['restaurant_name']) ?>
                                </a>
                            </p>
                            <?php if (!empty($review['comment'])): ?>
                                <p class="small text-muted mb-0">"<?= e($review['comment']) ?>"</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($myReviews === []): ?>
                        <p class="text-muted small mb-0">
                            No reviews yet — rate your completed orders to build your review history.
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent activity (Shopee security/activity style) -->
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h6 text-muted text-uppercase mb-3">Recent activity</h2>
                    <?php foreach ($activity as $entry): ?>
                        <div class="d-flex justify-content-between small py-1">
                            <span class="text-muted"><?= e(ucwords(str_replace('_', ' ', $entry['action']))) ?></span>
                            <span class="text-muted text-nowrap"><?= e(date('d M H:i', strtotime($entry['created_at']))) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($activity === []): ?>
                        <p class="text-muted small mb-0">No recent activity.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <!-- Address book -->
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h6 text-muted text-uppercase mb-3">📍 Saved addresses</h2>

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
                        <div class="col-md-4">
                            <label class="form-label" for="label">Label</label>
                            <input type="text" class="form-control" id="label" name="label"
                                   placeholder="e.g. Home" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="recipient_name">Recipient name</label>
                            <input type="text" class="form-control" id="recipient_name" name="recipient_name" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="address">Address</label>
                            <input type="text" class="form-control" id="address" name="address" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="city">City</label>
                            <input type="text" class="form-control" id="city" name="city">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="state">State</label>
                            <input type="text" class="form-control" id="state" name="state">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="postcode">Postcode</label>
                            <input type="text" class="form-control" id="postcode" name="postcode"
                                   maxlength="5" placeholder="e.g. 47300">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="phone">Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1">
                                <label class="form-check-label small" for="is_default">Default address</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn omnieats-btn btn-sm">Add address</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
