<?php
/**
 * Owner branch list (Module 2). Staff can view; only owners can
 * create/edit/delete (route guards + role check here).
 */
$isOwner = in_array('restaurant_owner', auth_user()['roles'], true);
?>
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h1 class="h4 mb-0">Branches</h1>
        <?php if ($isOwner): ?>
            <a class="btn omnieats-btn btn-sm" href="<?= url('/owner/branches/create') ?>">+ Add branch</a>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Address</th>
                        <th>Phone</th>
                        <th>Opening hours</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($branches as $branch): ?>
                        <tr>
                            <td>
                                <?= e($branch['name']) ?>
                                <?php if ((int) $branch['is_main'] === 1): ?>
                                    <span class="badge text-bg-secondary">Main</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($branch['address'] ?? '—') ?></td>
                            <td><?= e($branch['phone'] ?? '—') ?></td>
                            <td><?= e($branch['opening_hours'] ?? '—') ?></td>
                            <td class="text-end">
                                <?php if ($isOwner): ?>
                                    <a class="btn btn-sm btn-outline-secondary"
                                       href="<?= url('/owner/branches/' . (int) $branch['id'] . '/edit') ?>">Edit</a>
                                    <form class="d-inline" method="post"
                                          action="<?= url('/owner/branches/' . (int) $branch['id'] . '/delete') ?>"
                                          onsubmit="return confirm('Delete this branch? Past orders keep their records.');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted small">view only</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($branches === []): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">
                            No branches yet. Add your first branch to start taking orders.
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
