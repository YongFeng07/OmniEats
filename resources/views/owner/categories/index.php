<?php /** Owner category list (Module 3). */ ?>
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h1 class="h4 mb-0">Menu categories</h1>
        <a class="btn omnieats-btn btn-sm" href="<?= url('/owner/categories/create') ?>">+ Add category</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">#</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th style="width: 110px;">Sort order</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?= (int) $category['id'] ?></td>
                            <td class="fw-semibold"><?= e($category['name']) ?></td>
                            <td><?= e($category['description'] ?? '—') ?></td>
                            <td><?= (int) $category['sort_order'] ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-secondary"
                                   href="<?= url('/owner/categories/' . (int) $category['id'] . '/edit') ?>">Edit</a>
                                <form class="d-inline" method="post"
                                      action="<?= url('/owner/categories/' . (int) $category['id'] . '/delete') ?>"
                                      onsubmit="return confirm('Delete this category? Categories that still contain food items cannot be deleted.');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($categories === []): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">
                            No categories yet. Add categories to organise your menu.
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
