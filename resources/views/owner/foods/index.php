<?php
/**
 * Owner food item list (Module 3) with server-side search and category
 * filter, availability badges and a per-item availability toggle.
 */
$fmtPrice = fn (string $price): string => 'RM ' . number_format((float) $price, 2);
?>
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h1 class="h4 mb-0">Food items</h1>
        <a class="btn omnieats-btn btn-sm" href="<?= url('/owner/foods/create') ?>">+ Add food item</a>
    </div>

    <form class="row g-2 mb-3" method="get" action="<?= url('/owner/foods') ?>">
        <div class="col-md-6">
            <input type="text" name="q" class="form-control" placeholder="Search food items"
                   value="<?= e($q) ?>">
        </div>
        <div class="col-md-4">
            <select name="category" class="form-select">
                <option value="0">All categories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>" <?= $fCategory === (int) $category['id'] ? 'selected' : '' ?>>
                        <?= e($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-outline-secondary">Filter</button>
        </div>
    </form>

    <p class="text-muted small mb-2"><?= count($items) ?> item(s) found</p>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;"></th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Availability</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <?php if (!empty($item['image'])): ?>
                                    <img src="<?= asset($item['image']) ?>" alt=""
                                         style="width: 44px; height: 44px; object-fit: cover;" class="rounded">
                                <?php else: ?>
                                    <span style="font-size: 1.4rem;">🍽️</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="fw-semibold"><?= e($item['name']) ?></span>
                                <span class="d-block text-muted small"><?= e(mb_strimwidth($item['description'] ?? '', 0, 60, '…')) ?></span>
                            </td>
                            <td><?= e($item['category_name']) ?></td>
                            <td><?= e($fmtPrice($item['price'])) ?></td>
                            <td>
                                <?php if ((int) $item['is_available'] === 1): ?>
                                    <span class="badge text-bg-success">Available</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Unavailable</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-secondary"
                                   href="<?= url('/owner/foods/' . (int) $item['id'] . '/edit') ?>">Edit</a>
                                <form class="d-inline" method="post"
                                      action="<?= url('/owner/foods/' . (int) $item['id'] . '/toggle-available') ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-warning">
                                        <?= (int) $item['is_available'] === 1 ? 'Mark unavailable' : 'Mark available' ?>
                                    </button>
                                </form>
                                <form class="d-inline" method="post"
                                      action="<?= url('/owner/foods/' . (int) $item['id'] . '/delete') ?>"
                                      onsubmit="return confirm('Delete this food item? Past orders keep their records.');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($items === []): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No food items match the filters.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
