<?php /** Change own password (Module 1). */ ?>
<div class="container" style="max-width: 480px;">
    <div class="card shadow-sm mt-5 mb-5">
        <div class="card-body p-4">
            <h1 class="h4 mb-1">Change password</h1>
            <p class="text-muted small mb-4">Enter your current password, then choose a new one.</p>

            <form method="post" action="<?= url('/profile/password') ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="current_password">Current password</label>
                    <input type="password" class="form-control" id="current_password"
                           name="current_password" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="new_password">New password</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                    <div class="form-text">At least 8 characters, with letters and numbers.</div>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="new_password_confirmation">Confirm new password</label>
                    <input type="password" class="form-control" id="new_password_confirmation"
                           name="new_password_confirmation" required>
                </div>
                <button type="submit" class="btn omnieats-btn w-100">Update password</button>
            </form>

            <p class="text-muted small mt-3 mb-0">
                <a href="<?= url('/profile') ?>">&larr; Back to profile</a>
            </p>
        </div>
    </div>
</div>
