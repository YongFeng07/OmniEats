<?php
/**
 * Restaurant review management (Payment & Review module — Student 3).
 * Staff view every review of their restaurant (including admin-hidden
 * ones, flagged) and can reply publicly.
 *
 * Expects: $heading, $reviews.
 */
?>
<div class="container py-4" style="max-width: 860px;">
    <h1 class="h4 mb-4"><?= e($heading) ?></h1>

    <div class="d-flex flex-column gap-3">
        <?php foreach ($reviews as $review): ?>
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                        <p class="mb-0">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <?= $s <= (int) $review['rating'] ? '⭐' : '☆' ?>
                            <?php endfor; ?>
                            <span class="small text-muted ms-2">
                                <?= e($review['customer_name']) ?> ·
                                <?= e(date('d M Y', strtotime($review['created_at']))) ?>
                            </span>
                        </p>
                        <?php if ((int) $review['is_hidden'] === 1): ?>
                            <span class="badge text-bg-danger">Hidden by admin</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($review['comment'])): ?>
                        <p class="small mb-2">"<?= e($review['comment']) ?>"</p>
                    <?php endif; ?>

                    <?php if (!empty($review['reply'])): ?>
                        <div class="p-3 rounded-4 mb-3" style="background: #FDF0E9;">
                            <p class="small mb-1"><strong>Your reply</strong>
                                <span class="text-muted">
                                    · <?= e(date('d M Y', strtotime($review['replied_at']))) ?>
                                </span>
                            </p>
                            <p class="small mb-0"><?= e($review['reply']) ?></p>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="<?= url('/owner/reviews/' . (int) $review['id'] . '/reply') ?>" class="row g-2">
                        <?= csrf_field() ?>
                        <div class="col-12">
                            <input type="text" class="form-control form-control-sm" name="reply"
                                   maxlength="500" placeholder="Reply publicly to this review…"
                                   value="<?= e($review['reply'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-sm omnieats-btn">
                                <?= empty($review['reply']) ? 'Post reply' : 'Update reply' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if ($reviews === []): ?>
            <div class="alert alert-info mb-0">
                No reviews yet — reviews appear after customers complete orders.
            </div>
        <?php endif; ?>
    </div>
</div>
