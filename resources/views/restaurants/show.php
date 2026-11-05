<?php
/**
 * Public restaurant detail (Module 2). The availability control
 * feature (OPEN/CLOSED) is displayed clearly: while CLOSED the profile
 * stays viewable but a warning banner explains that new orders are
 * blocked (enforced at checkout in Module 5).
 */
$isOpen = (int) $restaurant['is_open'] === 1;
?>
<div class="container py-4" style="max-width: 960px;">
    <?php if (!empty($restaurant['cover_image'])): ?>
        <img src="<?= asset($restaurant['cover_image']) ?>" class="rounded-4 w-100 mb-4"
             alt="<?= e($restaurant['name']) ?> cover" style="max-height: 260px; object-fit: cover;">
    <?php endif; ?>

    <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
        <h1 class="h3 mb-0"><?= e($restaurant['name']) ?></h1>
        <?php if ($isOpen): ?>
            <span class="badge text-bg-success fs-6">● OPEN — accepting orders</span>
        <?php else: ?>
            <span class="badge text-bg-danger fs-6">● CLOSED</span>
        <?php endif; ?>
    </div>

    <?php if (!$isOpen): ?>
        <div class="alert alert-warning mt-3">
            This restaurant is currently <strong>closed</strong> and not accepting new orders.
            You can still browse its profile and menu.
        </div>
    <?php endif; ?>

    <div class="row g-4 mt-2">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h6 text-muted text-uppercase mb-3">About</h2>
                    <p class="mb-4"><?= e($restaurant['description'] ?: 'No description yet.') ?></p>

                    <table class="table table-sm mb-0">
                        <tr><th class="ps-0 text-muted" style="width: 40%;">Opening hours</th>
                            <td><?= e($restaurant['opening_hours']) ?></td></tr>
                        <tr><th class="ps-0 text-muted">Phone</th><td><?= e($restaurant['phone']) ?></td></tr>
                        <?php if (!empty($restaurant['email'])): ?>
                            <tr><th class="ps-0 text-muted">Email</th><td><?= e($restaurant['email']) ?></td></tr>
                        <?php endif; ?>
                        <tr><th class="ps-0 text-muted">Address</th>
                            <td><?= e($restaurant['address']) ?>, <?= e($restaurant['postcode']) ?>
                                <?= e($restaurant['city']) ?>, <?= e($restaurant['state']) ?></td></tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
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
                            <p class="text-muted small mb-0"><?= e($branch['opening_hours'] ?? '') ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info mt-4 mb-0">
        Menu browsing and ordering arrive with Module 3 (menu catalogue) and Module 5 (ordering) —
        this page already enforces the restaurant's availability status.
    </div>
</div>
