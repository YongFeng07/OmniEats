<?php
/**
 * Admin restaurant management (Module 2): search, approval status
 * filter, and the approval lifecycle actions.
 */
$statusBadge = function (string $status): string {
    return match ($status) {
        'approved'    => 'badge text-bg-success',
        'pending'     => 'badge text-bg-warning text-dark',
        'deactivated' => 'badge text-bg-secondary',
        default       => 'badge text-bg-secondary',
    };
};
?>
<div class="container py-4">
    <h1 class="h4 mb-3">Restaurant Management</h1>

    <form class="row g-2 mb-3" method="get" action="<?= url('/admin/restaurants') ?>">
        <div class="col-md-6">
            <input type="text" name="q" class="form-control" placeholder="Search restaurant or owner"
                   value="<?= e($q) ?>">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">All statuses</option>
                <option value="pending" <?= $fStatus === 'pending' ? 'selected' : '' ?>>Pending approval</option>
                <option value="approved" <?= $fStatus === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="deactivated" <?= $fStatus === 'deactivated' ? 'selected' : '' ?>>Deactivated</option>
            </select>
        </div>
        <div class="col-md-3 d-grid">
            <button type="submit" class="btn btn-outline-secondary">Filter</button>
        </div>
    </form>

    <p class="text-muted small mb-2"><?= count($restaurants) ?> restaurant(s) found</p>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Restaurant</th>
                        <th>Owner</th>
                        <th>Branches</th>
                        <th>Status</th>
                        <th>Open?</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($restaurants as $restaurant): ?>
                        <tr>
                            <td><?= (int) $restaurant['id'] ?></td>
                            <td><?= e($restaurant['name']) ?></td>
                            <td><?= e($restaurant['owner_name']) ?><br>
                                <span class="text-muted small"><?= e($restaurant['owner_email']) ?></span></td>
                            <td><?= (int) $restaurant['branch_count'] ?></td>
                            <td><span class="<?= $statusBadge($restaurant['status']) ?>">
                                <?= e(ucfirst($restaurant['status'])) ?></span></td>
                            <td>
                                <?php if ((int) $restaurant['is_open'] === 1): ?>
                                    <span class="badge text-bg-success">OPEN</span>
                                <?php else: ?>
                                    <span class="badge text-bg-danger">CLOSED</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary"
                                   href="<?= url('/admin/restaurants/' . (int) $restaurant['id']) ?>">View</a>
                                <?php if ($restaurant['status'] === 'pending'): ?>
                                    <form class="d-inline" method="post"
                                          action="<?= url('/admin/restaurants/' . (int) $restaurant['id'] . '/approve') ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                <?php elseif ($restaurant['status'] === 'deactivated'): ?>
                                    <form class="d-inline" method="post"
                                          action="<?= url('/admin/restaurants/' . (int) $restaurant['id'] . '/toggle-status') ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-success">Reactivate</button>
                                    </form>
                                <?php else: ?>
                                    <form class="d-inline" method="post"
                                          action="<?= url('/admin/restaurants/' . (int) $restaurant['id'] . '/toggle-status') ?>"
                                          onsubmit="return confirm('Deactivate this restaurant? It will be hidden from customers; records are preserved.');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Deactivate</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($restaurants === []): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No restaurants match the filters.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
