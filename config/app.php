<?php

/**
 * Application configuration.
 *
 * Loads key=value pairs from the project .env file (git-ignored) into
 * the process environment and returns a configuration array used
 * across the application. Secrets never live in source control.
 */

$envFile = dirname(__DIR__) . '/.env';
if (is_file($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        putenv(trim($key) . '=' . trim($value));
    }
}

return [
    'name' => getenv('APP_NAME') ?: 'OmniEats',
    'env'  => getenv('APP_ENV') ?: 'production',
    'url'  => rtrim(getenv('APP_URL') ?: '', '/'),
    'db'   => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'omnieats',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
    ],
];
