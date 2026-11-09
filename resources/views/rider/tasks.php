<?php
/**
 * Rider tasks (Delivery & Rider module — Student 3).
 * The rider's current assignment with the validated workflow:
 * accept/reject → pickup → out for delivery → delivered.
 *
 * Expects: $heading, $profile, $active (or null), $order (or null).
 */
$step = $active['status'] ?? '';
?>
<div class="container py-4" style="max-width: 760px;">
    <h1 class="h4 mb-4"><?= e($heading) ?></h1>

    <?php if ($active === null): ?>
        <div class="alert alert-info">
            You have no assigned delivery right now.
            <?php if ((int) $profile['is_available'] === 1): ?>
                Stay on shift — new assignments appear here automatically.
                <a class="alert-link" href="<?= url('/rider') ?>">Manage availability</a>.
            <?php else: ?>
                You are off shift — <a class="alert-link" href="<?= url('/rider') ?>">go on shift</a> to receive assignments.
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">Order <code><?= e($active['order_no']) ?></code></h2>
                    <span class="badge text-bg-primary"><?= e(ucwords(str_replace('_', ' ', $step))) ?></span>
                </div>

                <table class="table table-sm mb-0">
                    <tr><th class="ps-0 text-muted" style="width: 40%;">Deliver to</th>
                        <td><?= e($active['recipient_name'] ?? '—') ?></td></tr>
                    <tr><th class="ps-0 text-muted">Address</th>
                        <td><?= e($active['delivery_address'] ?? '—') ?></td></tr>
                    <tr><th class="ps-0 text-muted">Phone</th>
                        <td><?= e($active['delivery_phone'] ?? '—') ?></td></tr>
                    <?php if (!empty($active['assigned_at'])): ?>
                        <tr><th class="ps-0 text-muted">Assigned</th>
                            <td><?= e(date('d M Y, H:i', strtotime($active['assigned_at']))) ?></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($active['picked_up_at'])): ?>
                        <tr><th class="ps-0 text-muted">Picked up</th>
                            <td><?= e(date('d M Y, H:i', strtotime($active['picked_up_at']))) ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- Workflow actions — each step validated server-side -->
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <?php if ($step === 'assigned'): ?>
                    <p class="text-muted small mb-3">A delivery has been assigned to you.</p>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <form method="post" action="<?= url('/rider/tasks/' . (int) $active['id'] . '/respond') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="accept">
                            <button type="submit" class="btn btn-primary">✅ Accept assignment</button>
                        </form>
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="collapse"
                                data-bs-target="#reject-form">❌ Reject</button>
                    </div>
                    <div class="collapse" id="reject-form">
                        <form method="post" action="<?= url('/rider/tasks/' . (int) $active['id'] . '/respond') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="reject">
                            <div class="mb-2">
                                <label class="form-label" for="reason">Reason for rejecting</label>
                                <input type="text" class="form-control" id="reason" name="reason"
                                       maxlength="255" required>
                            </div>
                            <button type="submit" class="btn btn-danger btn-sm">Reject assignment</button>
                        </form>
                    </div>
                <?php elseif ($step === 'accepted'): ?>
                    <form method="post" action="<?= url('/rider/tasks/' . (int) $active['id'] . '/progress') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="step" value="pickup">
                        <button type="submit" class="btn btn-primary">🍱 Confirm food pickup</button>
                    </form>
                <?php elseif ($step === 'picked_up'): ?>
                    <form method="post" action="<?= url('/rider/tasks/' . (int) $active['id'] . '/progress') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="step" value="out_for_delivery">
                        <button type="submit" class="btn btn-primary">🛵 Start delivery (out for delivery)</button>
                    </form>
                <?php elseif ($step === 'out_for_delivery'): ?>
                    <form method="post" action="<?= url('/rider/tasks/' . (int) $active['id'] . '/progress') ?>"
                          onsubmit="return confirm('Confirm that the order was delivered?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="step" value="delivered">
                        <button type="submit" class="btn btn-success">⭐ Confirm delivery</button>
                    </form>
                <?php else: ?>
                    <p class="text-muted small mb-0">This delivery is finished.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
