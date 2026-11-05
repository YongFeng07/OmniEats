<?php
/**
 * Restaurant order list (Module 5) with status filter.
 */
$statusBadge = 'badge_class'; // shared helper in app/Helpers/functions.php
?>
<div class="container py-4">
    <h1 class="h4 mb-3">Orders</h1>

    <form class="row g-2 mb-3" method="get" action="<?= url('/owner/orders') ?>">
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">All statuses</option>
                <?php foreach (\App\Models\Order::statuses() as $status): ?>
                    <option value="<?= e($status) ?>" <?= $fStatus === $status ? 'selected' : '' ?>>
                        <?= e(ucwords(str_replace('_', ' ', $status))) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-grid">
            <button type="submit" class="btn btn-outline-secondary">Filter</button>
        </div>
    </form>

    <p class="text-muted small mb-2"><?= count($orders) ?> order(s)</p>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Order no.</th>
                        <th>Customer</th>
                        <th>Type</th>
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
                            <td><?= e($order['customer_name']) ?></td>
                            <td><?= e(ucfirst($order['fulfilment_type'])) ?></td>
                            <td><?= (int) ($order['item_count'] ?? 0) ?></td>
                            <td>RM <?= e(number_format((float) $order['total'], 2)) ?></td>
                            <td><span class="<?= $statusBadge($order['status']) ?>">
                                <?= e(ucwords(str_replace('_', ' ', $order['status']))) ?></span></td>
                            <td class="small"><?= e(date('d M H:i', strtotime($order['created_at']))) ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary"
                                   href="<?= url('/owner/orders/' . (int) $order['id']) ?>">Manage</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($orders === []): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No orders match the filter.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
