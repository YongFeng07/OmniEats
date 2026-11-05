<?php
/**
 * Platform activity overview (admin monitoring): the latest 100 audit
 * entries recorded across the whole platform.
 */
?>
<div class="container py-4">
    <h1 class="h4 mb-1">Activity Overview</h1>
    <p class="text-muted small mb-4">Latest 100 platform events, newest first.</p>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 130px;">When</th>
                        <th style="width: 150px;">Actor</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>Details</th>
                        <th style="width: 120px;">IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activities as $entry): ?>
                        <tr>
                            <td class="small text-muted"><?= e(date('d M H:i', strtotime($entry['created_at']))) ?></td>
                            <td><?= e($entry['actor_name'] ?? 'Guest/System') ?></td>
                            <td><code><?= e($entry['action']) ?></code></td>
                            <td class="small">
                                <?= e($entry['entity'] ?? '—') ?>
                                <?= $entry['entity_id'] !== null ? '#' . (int) $entry['entity_id'] : '' ?>
                            </td>
                            <td class="small"><?= e($entry['details'] ?? '') ?></td>
                            <td class="small text-muted"><?= e($entry['ip_address'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($activities === []): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No activity recorded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
