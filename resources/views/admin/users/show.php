<?php
/**
 * User details (Module 1): account info, roles, tenant, status and
 * recent platform activity for this user.
 */
$roles = $user['roles'] ?? [];
?>
<div class="container py-4" style="max-width: 900px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h1 class="h4 mb-0">User details — <?= e($user['name']) ?></h1>
        <div class="d-flex gap-2">
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
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h6 text-muted text-uppercase mb-3">Account</h2>
                    <table class="table table-sm mb-0">
                        <tr><th class="ps-0 text-muted">Name</th><td><?= e($user['name']) ?></td></tr>
                        <tr><th class="ps-0 text-muted">Email</th><td><?= e($user['email']) ?></td></tr>
                        <tr><th class="ps-0 text-muted">Phone</th><td><?= e($user['phone'] ?? '—') ?></td></tr>
                        <tr>
                            <th class="ps-0 text-muted">Role</th>
                            <td>
                                <?php foreach ($roles as $role): ?>
                                    <span class="badge text-bg-secondary"><?= e(str_replace('_', ' ', $role)) ?></span>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                        <tr>
                            <th class="ps-0 text-muted">Restaurant</th>
                            <td><?= e($restaurant['name'] ?? '—') ?></td>
                        </tr>
                        <tr>
                            <th class="ps-0 text-muted">Status</th>
                            <td>
                                <?php if ((int) $user['is_active'] === 1): ?>
                                    <span class="badge text-bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th class="ps-0 text-muted">Member since</th>
                            <td><?= e(date('d M Y, H:i', strtotime($user['created_at']))) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h6 text-muted text-uppercase mb-3">Recent activity</h2>
                    <?php if ($activities === []): ?>
                        <p class="text-muted mb-0">No recorded activity yet.</p>
                    <?php else: ?>
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>When</th><th>Action</th><th>Details</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activities as $entry): ?>
                                    <tr>
                                        <td class="text-muted small"><?= e(date('d M H:i', strtotime($entry['created_at']))) ?></td>
                                        <td><code><?= e($entry['action']) ?></code></td>
                                        <td class="small"><?= e($entry['details'] ?? '') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
