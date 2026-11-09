<?php
/**
 * Admin rider management (Delivery & Rider module — Student 3).
 * Rider accounts are created here ONLY — no public registration.
 * Suspend/Reactivate is the soft DELETE; availability is also
 * admin-controllable.
 */
?>
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h1 class="h4 mb-0">Riders</h1>
        <a class="btn omnieats-btn btn-sm" href="<?= url('/admin/riders/create') ?>">+ Register rider</a>
    </div>

    <p class="text-muted small mb-3">
        Rider accounts are created and activated by the administrator only —
        riders cannot register themselves.
    </p>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Vehicle</th>
                        <th>Status</th>
                        <th>Availability</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($riders as $rider): ?>
                        <tr>
                            <td><?= e($rider['name']) ?></td>
                            <td><?= e($rider['email']) ?></td>
                            <td><?= e($rider['phone'] ?? '—') ?></td>
                            <td class="small">
                                <?= e($rider['vehicle_type'] ?? '—') ?> ·
                                <code><?= e($rider['vehicle_plate'] ?? '—') ?></code>
                            </td>
                            <td>
                                <?php if ((int) $rider['is_active'] === 1): ?>
                                    <span class="badge text-bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge text-bg-danger">Suspended</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ((int) $rider['is_available'] === 1): ?>
                                    <span class="badge text-bg-primary">Available</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Unavailable</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary"
                                   href="<?= url('/admin/riders/' . (int) $rider['id'] . '/edit') ?>">Edit</a>
                                <form class="d-inline" method="post"
                                      action="<?= url('/admin/riders/' . (int) $rider['id'] . '/toggle-available') ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                        <?= (int) $rider['is_available'] === 1 ? 'Set unavailable' : 'Set available' ?>
                                    </button>
                                </form>
                                <form class="d-inline" method="post"
                                      action="<?= url('/admin/riders/' . (int) $rider['id'] . '/toggle-active') ?>"
                                      onsubmit="return confirm('<?= (int) $rider['is_active'] === 1 ? 'Suspend this rider?' : 'Reactivate this rider?' ?>');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm <?= (int) $rider['is_active'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                        <?= (int) $rider['is_active'] === 1 ? 'Suspend' : 'Reactivate' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($riders === []): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No riders registered yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
