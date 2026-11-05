<?php /** Sign-in form (Module 1). */ ?>
<div class="container" style="max-width: 420px;">
    <div class="card shadow-sm mt-5 mb-5">
        <div class="card-body p-4">
            <h1 class="h4 mb-1">Sign in to OmniEats</h1>
            <p class="text-muted small mb-4">Order food, manage your restaurant, or run the platform.</p>

            <form method="post" action="<?= url('/login') ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?= e(old('email')) ?>" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn omnieats-btn w-100">Sign in</button>
            </form>

            <p class="text-muted small mt-4 mb-0">
                New here? Customer and restaurant registration opens with Module 1 (Phase 5).
                Demo accounts: customer@omnieats.com · owner.nasilemak@omnieats.com ·
                superadmin@omnieats.com — password <code>Password@123</code>.
            </p>
        </div>
    </div>
</div>
