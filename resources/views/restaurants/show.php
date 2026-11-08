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
        <?php if ($restaurant['rating'] !== null): ?>
            <span class="badge text-bg-warning fs-6">⭐ <?= e(number_format((float) $restaurant['rating'], 1)) ?></span>
        <?php endif; ?>
        <?php if ($restaurant['delivery_minutes'] !== null): ?>
            <span class="badge text-bg-light border fs-6">🛵 <?= (int) $restaurant['delivery_minutes'] ?> min</span>
        <?php endif; ?>
        <?php if ((int) $restaurant['free_delivery'] === 1): ?>
            <span class="badge text-bg-info fs-6">🛵 Free Delivery</span>
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

    <div class="mt-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <h2 class="h4 mb-0">Menu</h2>
            <form class="row g-2" method="get" action="<?= url('/restaurants/' . (int) $restaurant['id']) ?>">
                <div class="col-auto">
                    <input type="text" name="q" class="form-control" placeholder="Search the menu"
                           value="<?= e($q) ?>">
                </div>
                <div class="col-auto">
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="0">All categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category['id'] ?>" <?= $fCategory === (int) $category['id'] ? 'selected' : '' ?>>
                                <?= e($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-secondary">Search</button>
                </div>
            </form>
        </div>

        <?php if ($menuItems === []): ?>
            <div class="alert alert-info">No menu items match your search.</div>
        <?php endif; ?>

        <?php foreach ($categories as $category): ?>
            <?php
            $categoryItems = array_values(array_filter(
                $menuItems,
                fn (array $item): bool => (int) $item['category_id'] === (int) $category['id']
            ));
            if ($categoryItems === []) {
                continue;
            }
            ?>
            <h3 class="h5 mt-4 mb-3"><?= e($category['name']) ?></h3>
            <div class="row g-3">
                <?php foreach ($categoryItems as $item): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <?php if (!empty($item['image'])): ?>
                                <img src="<?= asset($item['image']) ?>" class="card-img-top"
                                     alt="<?= e($item['name']) ?>" style="height: 140px; object-fit: cover;">
                            <?php else: ?>
                                <div class="card-img-top d-flex align-items-center justify-content-center bg-light"
                                     style="height: 140px;">
                                    <span style="font-size: 2.2rem;">🍽️</span>
                                </div>
                            <?php endif; ?>
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <h4 class="h6 mb-1">
                                        <a class="text-decoration-none text-dark"
                                           href="<?= url('/foods/' . (int) $item['id']) ?>"><?= e($item['name']) ?></a>
                                    </h4>
                                    <span class="fw-semibold text-nowrap">
                                        RM <?= e(number_format((float) $item['price'], 2)) ?>
                                    </span>
                                </div>
                                <p class="text-muted small mb-2"><?= e(mb_strimwidth($item['description'] ?? '', 0, 70, '…')) ?></p>
                                <?php if ((int) $item['is_available'] === 1): ?>
                                    <span class="badge text-bg-success">Available</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Unavailable</span>
                                <?php endif; ?>

                                <?php
                                $viewer = auth_user();
                                $isCustomer = $viewer && in_array('customer', $viewer['roles'], true);
                                ?>
                                <?php if ($isCustomer && (int) $item['is_available'] === 1 && $isOpen): ?>
                                    <form method="post" action="<?= url('/cart/add') ?>" class="d-flex gap-1 mt-2">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="food_item_id" value="<?= (int) $item['id'] ?>">
                                        <input type="number" name="quantity" value="1" min="1"
                                               class="form-control form-control-sm" style="width: 72px;" aria-label="Quantity">
                                        <button type="submit" class="btn btn-sm omnieats-btn">Add</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
