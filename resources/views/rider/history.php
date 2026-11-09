<?php
/**
 * Rider delivery history (Delivery & Rider module — Student 3).
 *
 * Expects: $heading, $profile, $history.
 */
?>
<div class="container py-4" style="max-width: 860px;">
    <h1 class="h4 mb-4"><?= e($heading) ?></h1>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Order</th>
                        <th>Restaurant</th>
                        <th>Items</th>
                        <th>Delivered</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $row): ?>
                        <tr>
                            <td><code><?= e($row['order_no']) ?></code></td>
                            <td><?= e($row['restaurant_name']) ?></td>
                            <td><?= (int) ($row['item_count'] ?? 0) ?></td>
                            <td class="small">
                                <?= !empty($row['delivered_at'])
                                    ? e(date('d M Y, H:i', strtotime($row['delivered_at'])))
                                    : '—' ?>
                            </td>
                            <td>
                                <span class="badge <?= $row['status'] === 'delivered' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                    <?= e(ucwords(str_replace('_', ' ', $row['status']))) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($history === []): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">
                            No completed deliveries yet.
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
