<?php
/**
 * ★ ADMIN MANUAL RIDER ASSIGNMENT (unique feature — Student 3).
 * Left: deliveries waiting for a rider (order READY + unassigned).
 * Right: eligible riders (active + available + no other active task).
 * Below each delivery: its assignment history (rejections/reassigns
 * are preserved and never overwritten).
 *
 * Expects: $heading, $awaiting, $trails, $riders.
 */
?>
<div class="container py-4">
    <h1 class="h4 mb-1"><?= e($heading) ?></h1>
    <p class="text-muted small mb-4">
        Only the administrator assigns riders. A rider must be active,
        available and free of another active delivery. Reassignments keep
        the previous assignment history.
    </p>

    <div class="row g-4">
        <div class="col-lg-7 d-flex flex-column gap-3">
            <h2 class="h6 text-muted text-uppercase mb-0">Deliveries waiting for a rider</h2>
            <?php foreach ($awaiting as $delivery): ?>
                <div class="incoming-order">
                    <div class="flex-grow-1">
                        <p class="fw-bold mb-1 text-dark">
                            <code><?= e($delivery['order_no']) ?></code>
                            · <?= e($delivery['customer_name']) ?>
                        </p>
                        <p class="small text-muted mb-0">
                            <?= e($delivery['address'] ?? '—') ?>
                            · 📞 <?= e($delivery['phone'] ?? '—') ?>
                        </p>

                        <?php if (!empty($trails[(int) $delivery['id']])): ?>
                            <div class="small text-muted mt-2">
                                <strong>Assignment history</strong>
                                <ul class="mb-0 ps-3">
                                    <?php foreach ($trails[(int) $delivery['id']] as $entry): ?>
                                        <li>
                                            <?= e($entry['rider_name']) ?> —
                                            <strong><?= e(ucfirst($entry['status'])) ?></strong>
                                            <?= e(date('d M H:i', strtotime($entry['assigned_at']))) ?>
                                            <?php if (!empty($entry['rejection_reason'])): ?>
                                                · "<?= e($entry['rejection_reason']) ?>"
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>

                    <form method="post" action="<?= url('/admin/assignments/' . (int) $delivery['id'] . '/assign') ?>"
                          class="d-flex gap-2 align-items-center">
                        <?= csrf_field() ?>
                        <select name="rider_id" class="form-select form-select-sm" style="min-width: 170px;" required>
                            <option value="">Choose rider…</option>
                            <?php foreach ($riders as $rider): ?>
                                <option value="<?= (int) $rider['user_id'] ?>">
                                    <?= e($rider['name']) ?> ·
                                    <?= e($rider['vehicle_plate'] ?? '—') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary"
                            <?= $riders === [] ? 'disabled' : '' ?>>Assign</button>
                    </form>
                </div>
            <?php endforeach; ?>

            <?php if ($awaiting === []): ?>
                <div class="alert alert-info mb-0">
                    No deliveries waiting for assignment right now.
                    Deliveries appear here once a restaurant marks its order READY.
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-5">
            <h2 class="h6 text-muted text-uppercase mb-3">Eligible riders</h2>
            <div class="d-flex flex-column gap-2">
                <?php foreach ($riders as $rider): ?>
                    <div class="p-3 rounded-4" style="border: 1.5px solid var(--cream-line);">
                        <p class="mb-0 fw-semibold text-dark">🧑‍✈️ <?= e($rider['name']) ?>
                            <span class="badge text-bg-primary">Available</span>
                        </p>
                        <p class="small text-muted mb-0">
                            <?= e($rider['vehicle_type'] ?? '—') ?> ·
                            <code><?= e($rider['vehicle_plate'] ?? '—') ?></code>
                            · 📞 <?= e($rider['phone'] ?? '—') ?>
                        </p>
                    </div>
                <?php endforeach; ?>
                <?php if ($riders === []): ?>
                    <div class="alert alert-warning mb-0">
                        No eligible riders — a rider must be active, available
                        and free of another active delivery.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
