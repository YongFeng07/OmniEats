<?php
/**
 * Admin user list (Module 1) with search and role/status filters.
 */
function roleLabel(string $name): string
{
    return ucwords(str_replace('_', ' ', $name));
}
?>
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h1 class="h4 mb-0">User Management</h1>
        <a class="btn omnieats-btn btn-sm" href="<?= url('/admin/users/create') ?>">+ New user</a>
    </div>

    <form class="row g-2 mb-3" method="get" action="<?= url('/admin/users') ?>">
        <div class="col-md-5">
            <input type="text" name="q" class="form-control" placeholder="Search name or email"
                   value="<?= e($q) ?>">
        </div>
        <div class="col-md-3">
            <select name="role" class="form-select">
                <option value="">All roles</option>
                <?php foreach ($roles as $role): ?>
                    <option value="<?= e($role['name']) ?>" <?= $fRole === $role['name'] ? 'selected' : '' ?>>
                        <?= e(roleLabel($role['name'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select">
                <option value="">All statuses</option>
                <option value="active" <?= $fStatus === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $fStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-outline-secondary">Filter</button>
        </div>
    </form>

    <p class="text-muted small mb-2"><?= count($users) ?> user(s) found</p>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Restaurant</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <?php $roleNames = $user['role_names'] !== null ? explode(',', $user['role_names']) : []; ?>
                        <tr>
                            <td><?= (int) $user['id'] ?></td>
                            <td><?= e($user['name']) ?></td>
                            <td><?= e($user['email']) ?></td>
                            <td>
                                <?php foreach ($roleNames as $roleName): ?>
                                    <span class="badge text-bg-secondary"><?= e(roleLabel($roleName)) ?></span>
                                <?php endforeach; ?>
                            </td>
                            <td><?= e($user['restaurant_name'] ?? '—') ?></td>
                            <td>
                                <?php if ((int) $user['is_active'] === 1): ?>
                                    <span class="badge text-bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary"
                                   href="<?= url('/admin/users/' . (int) $user['id']) ?>">View</a>
                                <a class="btn btn-sm btn-outline-secondary"
                                   href="<?= url('/admin/users/' . (int) $user['id'] . '/edit') ?>">Edit</a>
                                <form class="d-inline" method="post"
                                      action="<?= url('/admin/users/' . (int) $user['id'] . '/toggle-status') ?>"
                                      onsubmit="return confirm('<?= (int) $user['is_active'] === 1
                                          ? 'Deactivate this account? The user will be signed out immediately.'
                                          : 'Reactivate this account?' ?>');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <?= (int) $user['is_active'] === 1 ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($users === []): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No users match the filters.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
