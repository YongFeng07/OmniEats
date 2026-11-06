<?php
/**
 * Public restaurant listing (Module 2). Shows APPROVED restaurants
 * with their OPEN/CLOSED availability status, rendered as reusable
 * Cream Soft UI cards (shared/restaurant_card.php).
 */
?>
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h1 class="h3 mb-0">Browse restaurants</h1>
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
            <?php view('shared/restaurant_card', ['restaurant' => $restaurant], null); ?>
        <?php endforeach; ?>

        <?php if ($restaurants === []): ?>
            <div class="col-12">
                <div class="alert alert-info mb-0">No restaurants match your search.</div>
            </div>
        <?php endif; ?>
    </div>
</div>
