<?php
/**
 * Customer order history (Module 5).
 */
$statusBadge = 'badge_class'; // shared helper in app/Helpers/functions.php
?>
<div class="container py-4" style="max-width: 960px;">
    <h1 class="h4 mb-3">My Orders</h1>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Order no.</th>
                        <th>Restaurant</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Placed</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><code><?= e($order['order_no']) ?></code></td>
                            <td><?= e($order['restaurant_name']) ?></td>
                            <td><?= (int) ($order['item_count'] ?? 0) ?></td>
                            <td>RM <?= e(number_format((float) $order['total'], 2)) ?></td>
                            <td><span class="<?= $statusBadge($order['status']) ?>">
                                <?= e(ucwords(str_replace('_', ' ', $order['status']))) ?></span></td>
                            <td class="small"><?= e(date('d M Y, H:i', strtotime($order['created_at']))) ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary"
                                   href="<?= url('/orders/' . (int) $order['id']) ?>">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($orders === []): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">
                            No orders yet. <a href="<?= url('/restaurants') ?>">Order your first meal</a>.
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
