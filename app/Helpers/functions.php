<?php

/**
 * Global helper functions used by controllers and views.
 */

/** Escape output to prevent XSS. Used for every dynamic value in views. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Build an absolute URL from a path, e.g. url('/login').
 * The base is derived from the current request (or APP_URL when set),
 * so the app works on any host/port and under a subdirectory.
 */
function url(string $path = ''): string
{
    static $base = null;

    if ($base === null) {
        $config = require __DIR__ . '/../../config/app.php';

        if ($config['url'] !== '') {
            $base = $config['url'];
        } else {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
            $base   = $scheme . '://' . $host . $dir;
        }
    }

    return $base . $path;
}

/** Redirect to another route and stop the current request. */
function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

/**
 * Absolute URL for a file inside public/, e.g. asset('uploads/x.png').
 * Used for uploaded images whose paths are stored in the database.
 */
function asset(string $path): string
{
    return url('/' . ltrim($path, '/'));
}

/** Read (or, given a $message, set) a one-time flash message. */
function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $value;
}

/** CSRF token — one per session, derived from random bytes. */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

/** Hidden input to place inside every <form method="post">. */
function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

/** The currently logged-in user (row from users, with `roles`), or null. */
function auth_user(): ?array
{
    return App\Services\AuthService::user();
}

/** Repopulate a form field after a validation error. */
function old(string $key, string $default = ''): string
{
    return $_SESSION['_old'][$key] ?? $default;
}

/**
 * Render a view file. Variables in $data become local variables inside
 * the view. The rendered HTML is placed inside the master layout
 * (shared/layout.php); pass $layout = null for a standalone page.
 */
function view(string $path, array $data = [], ?string $layout = 'shared/layout'): void
{
    extract($data, EXTR_SKIP);

    ob_start();
    require __DIR__ . '/../../resources/views/' . $path . '.php';
    $content = ob_get_clean();

    if ($layout !== null) {
        require __DIR__ . '/../../resources/views/' . $layout . '.php';
    } else {
        echo $content;
    }
}

/** Abort with a friendly error page (404, 403). */
function abort(int $code, string $message = ''): never
{
    http_response_code($code);
    view('errors/' . $code, ['title' => 'Error', 'message' => $message]);
    exit;
}

/** Append a row to the platform activity log (admin monitoring). */
function activity(string $action, ?string $entity = null, ?int $entityId = null, ?string $details = null): void
{
    $stmt = db()->prepare(
        'INSERT INTO activity_logs (user_id, action, entity, entity_id, details, ip_address)
         VALUES (:uid, :action, :entity, :eid, :details, :ip)'
    );
    $stmt->execute([
        'uid'     => $_SESSION['user_id'] ?? null,
        'action'  => $action,
        'entity'  => $entity,
        'eid'     => $entityId,
        'details' => $details,
        'ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}
