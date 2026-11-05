<?php /** 403 — forbidden (RBAC or CSRF). */ ?>
<div class="container text-center py-5">
    <h1 class="display-3 fw-bold">403</h1>
    <p class="lead"><?= e($message ?: 'You do not have permission to access this page.') ?></p>
    <a class="btn omnieats-btn" href="<?= url('/') ?>">Back to home</a>
</div>
