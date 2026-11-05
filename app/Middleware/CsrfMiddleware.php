<?php

namespace App\Middleware;

/**
 * CSRF protection: every POST request must carry the session's token
 * in a hidden `_token` field. Forms add it with <?= csrf_field() ?>.
 * Applied globally in public/index.php before routing.
 */
class CsrfMiddleware
{
    public static function verify(): void
    {
        $sent = $_POST['_token'] ?? '';

        if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
            abort(403, 'Invalid or missing CSRF token. Please go back and try again.');
        }
    }
}
