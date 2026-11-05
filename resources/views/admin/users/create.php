<?php
/**
 * Admin "create user" form (Module 1). The restaurant dropdown is only
 * required for owner/staff roles — toggled by a small script and
 * enforced again on the server (UserCreateValidator + UserService).
 */
?>
<div class="container py-4" style="max-width: 640px;">
    <h1 class="h4 mb-1">New user</h1>
    <p class="text-muted small mb-4">Create a customer, restaurant staff, restaurant owner or administrator account.</p>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form method="post" action="<?= url('/admin/users/create') ?>">
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
                           value="<?= e(old('phone')) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="role">Role</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="">Choose a role…</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= e($role['name']) ?>" <?= old('role') === $role['name'] ? 'selected' : '' ?>>
                                <?= e(ucwords(str_replace('_', ' ', $role['name']))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3" id="restaurant-row" style="display:none;">
                    <label class="form-label" for="restaurant_id">Restaurant (tenant)</label>
                    <select class="form-select" id="restaurant_id" name="restaurant_id">
                        <option value="">Choose a restaurant…</option>
                        <?php foreach ($restaurants as $restaurant): ?>
                            <option value="<?= (int) $restaurant['id'] ?>" <?= old('restaurant_id') === (string) $restaurant['id'] ? 'selected' : '' ?>>
                                <?= e($restaurant['name']) ?> (<?= e($restaurant['status']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
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
                <div class="d-flex gap-2">
                    <button type="submit" class="btn omnieats-btn">Create user</button>
                    <a class="btn btn-outline-secondary" href="<?= url('/admin/users') ?>">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Show the restaurant dropdown only for tenant-linked roles.
    // Server-side validation enforces the same rule.
    const roleSelect = document.getElementById('role');
    const restaurantRow = document.getElementById('restaurant-row');
    function syncRestaurantRow() {
        restaurantRow.style.display =
            ['restaurant_owner', 'restaurant_staff'].includes(roleSelect.value) ? '' : 'none';
    }
    roleSelect.addEventListener('change', syncRestaurantRow);
    syncRestaurantRow();
</script>
