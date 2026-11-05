<?php
/**
 * Owner subscription page (Module 4): current plan + quota usage,
 * plan picker with simulated payment, history, and cancel.
 */
$subscription = $quota['subscription'];
$plan = $quota['plan'];
$fmtDate = fn (?string $d): string => $d ? date('d M Y', strtotime($d)) : '—';
?>
<div class="container py-4" style="max-width: 980px;">
    <h1 class="h4 mb-4">My Subscription</h1>

    <?php if ($subscription === null): ?>
        <div class="alert alert-warning">
            <strong>No active subscription.</strong>
            Choose a plan below — even the Free plan gives you a starter quota.
            Branch and food item creation is blocked until you subscribe.
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <h2 class="h5 mb-1">
                            <?= e($plan['name'] ?? $subscription['plan_name']) ?> plan
                            <span class="<?= badge_class('active') ?>">Active</span>
                        </h2>
                        <p class="text-muted small mb-3">
                            RM <?= e(number_format((float) $subscription['price_paid'], 2)) ?>/month ·
                            renews <?= e($fmtDate($subscription['ends_at'])) ?>
                        </p>
                    </div>
                    <form method="post" action="<?= url('/owner/subscription/cancel') ?>"
                          onsubmit="return confirm('Cancel your subscription? New branch and food item creation will be blocked until you subscribe again.');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline-danger">Cancel subscription</button>
                    </form>
                </div>

                <h3 class="h6 text-muted text-uppercase mb-2">Plan usage</h3>
                <div class="row g-3">
                    <div class="col-md-6">
                        <p class="small mb-1">
                            Branches: <strong><?= (int) $quota['branches'] ?> / <?= (int) $quota['max_branches'] ?></strong>
                        </p>
                        <div class="progress" role="progressbar" style="height: 10px;">
                            <?php $pct = $quota['max_branches'] > 0
                                ? min(100, round($quota['branches'] / $quota['max_branches'] * 100)) : 0; ?>
                            <div class="progress-bar omnieats-progress" style="width: <?= $pct ?>%"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <p class="small mb-1">
                            Food items: <strong><?= (int) $quota['food_items'] ?> /
                            <?= $quota['max_food_items'] !== null ? (int) $quota['max_food_items'] : 'Unlimited' ?></strong>
                        </p>
                        <div class="progress" role="progressbar" style="height: 10px;">
                            <?php $pct = $quota['max_food_items'] !== null && $quota['max_food_items'] > 0
                                ? min(100, round($quota['food_items'] / $quota['max_food_items'] * 100)) : 0; ?>
                            <div class="progress-bar omnieats-progress" style="width: <?= $pct ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <h2 class="h5 mb-3">Available plans</h2>
    <div class="row g-4 mb-4">
        <?php foreach ($plans as $candidate): ?>
            <?php $isCurrent = $subscription !== null && (int) $subscription['plan_id'] === (int) $candidate['id']; ?>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4 d-flex flex-column">
                        <h3 class="h5 mb-1"><?= e($candidate['name']) ?></h3>
                        <p class="h4 mb-3">
                            RM <?= e(number_format((float) $candidate['price'], 2)) ?>
                            <span class="fs-6 text-muted fw-normal">/month</span>
                        </p>
                        <ul class="small text-secondary mb-4">
                            <?php foreach (explode("\n", $candidate['features'] ?? '') as $feature): ?>
                                <?php if (trim($feature) !== ''): ?>
                                    <li><?= e(trim($feature)) ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                        <form method="post" class="mt-auto"
                              action="<?= url('/owner/subscription/subscribe/' . (int) $candidate['id']) ?>"
                              onsubmit="return confirm('<?= $isCurrent
                                  ? 'Renew ' . e($candidate['name']) . ' for another month?'
                                  : 'Switch to ' . e($candidate['name']) . '? Payment is simulated.' ?>');">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn w-100 <?= $isCurrent ? 'btn-outline-secondary' : 'omnieats-btn' ?>">
                                <?= $isCurrent ? 'Renew' : (($subscription === null) ? 'Choose plan' : 'Switch to ' . e($candidate['name'])) ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <h2 class="h5 mb-3">Subscription history</h2>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Plan</th>
                        <th>Price paid</th>
                        <th>Starts</th>
                        <th>Ends</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $row): ?>
                        <tr>
                            <td><?= (int) $row['id'] ?></td>
                            <td class="fw-semibold"><?= e($row['plan_name']) ?></td>
                            <td>RM <?= e(number_format((float) $row['price_paid'], 2)) ?></td>
                            <td class="small"><?= e($fmtDate($row['starts_at'])) ?></td>
                            <td class="small"><?= e($fmtDate($row['ends_at'])) ?></td>
                            <td>
                                <span class="<?= badge_class($row['status']) ?>">
                                    <?= e(ucfirst($row['status'])) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($history === []): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No subscription history yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
