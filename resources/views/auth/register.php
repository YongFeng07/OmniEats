<?php
/**
 * Registration form (Module 1). Shared by customer sign-up
 * (/register) and restaurant owner sign-up (/register/restaurant) —
 * the controller passes $action, $heading, $subtext and $isOwner.
 */
?>
<div class="container" style="max-width: 480px;">
    <?php if (!$isOwner): ?>
        <div class="mt-4 p-4 rounded-4" style="background: #FDF0E9; border: 1.5px solid var(--cream-line);">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <p class="fw-bold text-dark mb-1">🍽️ Own a restaurant?</p>
                    <p class="small text-muted mb-0">Start selling on OmniEats — set up your menu and receive orders.</p>
                </div>
                <a class="btn omnieats-btn btn-sm" href="<?= url('/register/restaurant') ?>">Partner with us →</a>
            </div>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mt-4 mb-5">
        <div class="card-body p-4">
            <h1 class="h4 mb-1"><?= e($heading) ?></h1>
            <p class="text-muted small mb-4"><?= e($subtext) ?></p>

            <form method="post" action="<?= e($action) ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="name">Full name</label>
                    <input type="text" class="form-control" id="name" name="name"
                           value="<?= e(old('name')) ?>" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?= e(old('email')) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="phone">Phone <span class="text-muted">(optional)</span></label>
                    <input type="tel" class="form-control" id="phone" name="phone"
                           value="<?= e(old('phone')) ?>" placeholder="012-3456789">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="form-text">At least 8 characters, with letters and numbers.</div>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password_confirmation">Confirm password</label>
                    <input type="password" class="form-control" id="password_confirmation"
                           name="password_confirmation" required>
                </div>
                <button type="submit" class="btn omnieats-btn w-100">Create account</button>
            </form>

            <p class="text-muted small mt-4 mb-0">
                <?php if ($isOwner): ?>
                    Just looking to order? <a href="<?= url('/register') ?>">Register as a customer</a>.
                <?php else: ?>
                    Running a restaurant? <a href="<?= url('/register/restaurant') ?>">Register your restaurant</a>.
                <?php endif; ?>
                Already have an account? <a href="<?= url('/login') ?>">Sign in</a>.
            </p>
        </div>
    </div>
</div>
