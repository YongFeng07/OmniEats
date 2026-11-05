<?php
/**
 * Admin plan list (Module 4): limits, prices, subscriber counts and
 * archive/restore actions.
 */
?>
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h1 class="h4 mb-0">Subscription Plans</h1>
        <a class="btn omnieats-btn btn-sm" href="<?= url('/admin/plans/create') ?>">+ New plan</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Plan</th>
                        <th>Price / month</th>
                        <th>Branches</th>
                        <th>Food items</th>
                        <th>Active subscribers</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plans as $plan): ?>
                        <tr>
                            <td>
                                <span class="fw-semibold"><?= e($plan['name']) ?></span>
                                <span class="d-block text-muted small"><?= e(mb_strimwidth($plan['description'] ?? '', 0, 60, '…')) ?></span>
                            </td>
                            <td>RM <?= e(number_format((float) $plan['price'], 2)) ?></td>
                            <td><?= (int) $plan['max_branches'] ?></td>
                            <td><?= $plan['max_food_items'] !== null ? (int) $plan['max_food_items'] : 'Unlimited' ?></td>
                            <td><?= (int) $plan['active_subscribers'] ?></td>
                            <td>
                                <?php if ((int) $plan['is_active'] === 1): ?>
                                    <span class="badge text-bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Archived</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-secondary"
                                   href="<?= url('/admin/plans/' . (int) $plan['id'] . '/edit') ?>">Edit</a>
                                <form class="d-inline" method="post"
                                      action="<?= url('/admin/plans/' . (int) $plan['id'] . '/toggle-active') ?>"
                                      onsubmit="return confirm('<?= (int) $plan['is_active'] === 1
                                          ? 'Archive this plan? Plans with active subscribers cannot be archived.'
                                          : 'Restore this plan?' ?>');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <?= (int) $plan['is_active'] === 1 ? 'Archive' : 'Restore' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
