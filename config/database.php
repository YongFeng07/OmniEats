<?php

/**
 * PDO connection factory.
 *
 * A single shared PDO instance is created on the first call and reused
 * for the whole request. All queries in the application use prepared
 * statements — values are never concatenated into SQL strings.
 */

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $config = require __DIR__ . '/app.php';
        $db = $config['db'];

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $db['host'],
            $db['port'],
            $db['name']
        );

        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    return $pdo;
}
