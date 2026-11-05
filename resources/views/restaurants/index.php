<?php
/**
 * Public restaurant listing (Module 2). Shows APPROVED restaurants
 * with their OPEN/CLOSED availability status.
 */
?>
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h1 class="h4 mb-0">Browse restaurants</h1>
        <form class="row g-2" method="get" action="<?= url('/restaurants') ?>">
            <div class="col-auto">
                <input type="text" name="q" class="form-control" placeholder="Search name, city or description"
                       value="<?= e($q) ?>">
            </div>
            <div class="col-auto">
                <select name="open" class="form-select">
                    <option value="">All restaurants</option>
                    <option value="open" <?= $fOpen === 'open' ? 'selected' : '' ?>>Open only</option>
                    <option value="closed" <?= $fOpen === 'closed' ? 'selected' : '' ?>>Closed only</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-secondary">Search</button>
            </div>
        </form>
    </div>

    <p class="text-muted small mb-3"><?= count($restaurants) ?> restaurant(s) found</p>

    <div class="row g-4">
        <?php foreach ($restaurants as $restaurant): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <?php if (!empty($restaurant['logo'])): ?>
                        <img src="<?= asset($restaurant['logo']) ?>" class="card-img-top"
                             alt="<?= e($restaurant['name']) ?> logo"
                             style="height: 160px; object-fit: cover;">
                    <?php else: ?>
                        <div class="card-img-top d-flex align-items-center justify-content-center bg-light"
                             style="height: 160px;">
                            <span class="display-6">🍽️</span>
                        </div>
                    <?php endif; ?>
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <h2 class="h5 mb-0">
                                <a class="text-decoration-none text-dark" href="<?= url('/restaurants/' . (int) $restaurant['id']) ?>">
                                    <?= e($restaurant['name']) ?>
                                </a>
                            </h2>
                            <?php if ((int) $restaurant['is_open'] === 1): ?>
                                <span class="badge text-bg-success">OPEN</span>
                            <?php else: ?>
                                <span class="badge text-bg-danger">CLOSED</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-muted small mb-2">
                            <?= e($restaurant['city']) ?> · <?= e($restaurant['opening_hours']) ?>
                        </p>
                        <p class="text-secondary small mb-3" style="min-height: 2.5rem;">
                            <?= e(mb_strimwidth($restaurant['description'] ?? '', 0, 110, '…')) ?>
                        </p>
                        <a class="btn btn-sm btn-outline-primary"
                           href="<?= url('/restaurants/' . (int) $restaurant['id']) ?>">View restaurant</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if ($restaurants === []): ?>
            <div class="col-12">
                <div class="alert alert-info mb-0">No restaurants match your search.</div>
            </div>
        <?php endif; ?>
    </div>
</div>
