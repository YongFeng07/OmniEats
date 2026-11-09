<?php
/**
 * Admin review moderation (Payment & Review module — Student 3).
 * Hide/unhide inappropriate reviews; the action is recorded on the
 * review row and hidden reviews disappear from public pages.
 *
 * Expects: $heading, $reviews.
 */
?>
<div class="container py-4">
    <h1 class="h4 mb-4"><?= e($heading) ?></h1>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Restaurant</th>
                        <th>Customer</th>
                        <th>Rating</th>
                        <th>Comment</th>
                        <th>Reply</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reviews as $review): ?>
                        <tr>
                            <td><?= e($review['restaurant_name']) ?></td>
                            <td><?= e($review['customer_name']) ?></td>
                            <td>
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <?= $s <= (int) $review['rating'] ? '⭐' : '☆' ?>
                                <?php endfor; ?>
                            </td>
                            <td class="small" style="max-width: 260px;">
                                <?= e(mb_strimwidth($review['comment'] ?? '', 0, 70, '…')) ?>
                            </td>
                            <td class="small">
                                <?= $review['reply'] !== null
                                    ? '✅ ' . e(mb_strimwidth($review['reply'], 0, 40, '…'))
                                    : '<span class="text-muted">—</span>' ?>
                            </td>
                            <td>
                                <?php if ((int) $review['is_hidden'] === 1): ?>
                                    <span class="badge text-bg-danger">Hidden</span>
                                <?php else: ?>
                                    <span class="badge text-bg-success">Visible</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <form class="d-inline" method="post"
                                      action="<?= url('/admin/reviews/' . (int) $review['id'] . '/toggle') ?>"
                                      onsubmit="return confirm('<?= (int) $review['is_hidden'] === 1 ? 'Show this review publicly again?' : 'Hide this review from public pages?' ?>');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm <?= (int) $review['is_hidden'] === 1 ? 'btn-outline-success' : 'btn-outline-danger' ?>">
                                        <?= (int) $review['is_hidden'] === 1 ? 'Unhide' : 'Hide' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($reviews === []): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No reviews yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
