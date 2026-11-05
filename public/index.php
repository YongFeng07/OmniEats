<?php

declare(strict_types=1);

/**
 * OmniEats — front controller.
 *
 * Every request enters here. The router (app/Core/Router.php) selects
 * the controller, route guards enforce authentication/RBAC, and the
 * CSRF middleware protects every POST.
 */

error_reporting(E_ALL);

$config = require __DIR__ . '/../config/app.php';

// During local development errors are shown; in production the friendly
// 500 page is displayed and details go to the PHP error log instead.
ini_set('display_errors', $config['env'] === 'local' ? '1' : '0');

// Uncaught exceptions: log the real message, show a safe page.
set_exception_handler(function (Throwable $e): void {
    error_log('[' . date('c') . '] ' . get_class($e) . ': ' . $e->getMessage()
        . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    require __DIR__ . '/../resources/views/errors/500.php';
    exit;
});

session_name('omnieats_session');
session_start();

require __DIR__ . '/../app/Helpers/functions.php';
require __DIR__ . '/../config/database.php';

// PSR-4-style autoloader for the App\ namespace (no Composer needed).
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = __DIR__ . '/../app/' . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// CSRF: every POST must carry the session token.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    App\Middleware\CsrfMiddleware::verify();
}

$router = new App\Core\Router();
require __DIR__ . '/../routes/web.php';
$router->dispatch($_SERVER['REQUEST_METHOD'], (string) ($_SERVER['REQUEST_URI'] ?? '/'));
