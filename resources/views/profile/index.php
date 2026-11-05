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
</div>
