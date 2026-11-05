<?php
/**
 * Admin restaurant detail (Module 2): profile, owner, branches and
 * the approval lifecycle actions.
 */
?>
<div class="container py-4" style="max-width: 960px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h1 class="h4 mb-0"><?= e($restaurant['name']) ?></h1>
        <div class="d-flex gap-2">
            <?php if ($restaurant['status'] === 'pending'): ?>
                <form method="post" action="<?= url('/admin/restaurants/' . (int) $restaurant['id'] . '/approve') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-success">Approve</button>
                </form>
            <?php elseif ($restaurant['status'] === 'deactivated'): ?>
                <form method="post" action="<?= url('/admin/restaurants/' . (int) $restaurant['id'] . '/toggle-status') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-success">Reactivate</button>
                </form>
            <?php else: ?>
                <form method="post" action="<?= url('/admin/restaurants/' . (int) $restaurant['id'] . '/toggle-status') ?>"
                      onsubmit="return confirm('Deactivate this restaurant? Records are preserved.');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-danger">Deactivate</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h2 class="h6 text-muted text-uppercase mb-3">Profile</h2>
                    <table class="table table-sm mb-0">
                        <tr><th class="ps-0 text-muted">Status</th>
                            <td><span class="badge text-bg-secondary"><?= e(ucfirst($restaurant['status'])) ?></span></td></tr>
                        <tr><th class="ps-0 text-muted">Availability</th>
                            <td><?= (int) $restaurant['is_open'] === 1 ? 'OPEN' : 'CLOSED' ?></td></tr>
                        <tr><th class="ps-0 text-muted">Slug</th><td><?= e($restaurant['slug']) ?></td></tr>
                        <tr><th class="ps-0 text-muted">Phone</th><td><?= e($restaurant['phone']) ?></td></tr>
                        <tr><th class="ps-0 text-muted">Email</th><td><?= e($restaurant['email'] ?? '—') ?></td></tr>
                        <tr><th class="ps-0 text-muted">Address</th>
                            <td><?= e($restaurant['address']) ?>, <?= e($restaurant['postcode']) ?>
                                <?= e($restaurant['city']) ?>, <?= e($restaurant['state']) ?></td></tr>
                        <tr><th class="ps-0 text-muted">Opening hours</th>
                            <td><?= e($restaurant['opening_hours']) ?></td></tr>
                        <tr><th class="ps-0 text-muted">Registered</th>
                            <td><?= e(date('d M Y, H:i', strtotime($restaurant['created_at']))) ?></td></tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h2 class="h6 text-muted text-uppercase mb-3">Owner</h2>
                    <p class="mb-1 fw-semibold"><?= e($owner['name']) ?></p>
                    <p class="text-muted small mb-0"><?= e($owner['email']) ?></p>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h6 text-muted text-uppercase mb-3">Branches (<?= count($branches) ?>)</h2>
                    <?php foreach ($branches as $branch): ?>
                        <div class="mb-3 pb-3 border-bottom">
                            <p class="fw-semibold mb-1">
                                <?= e($branch['name']) ?>
                                <?php if ((int) $branch['is_main'] === 1): ?>
                                    <span class="badge text-bg-secondary">Main</span>
                                <?php endif; ?>
                            </p>
                            <p class="text-muted small mb-0"><?= e($branch['address'] ?? '—') ?></p>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($branches === []): ?>
                        <p class="text-muted small mb-0">No branches yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
