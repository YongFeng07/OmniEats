<?php

namespace App\Models;

use PDO;

/**
 * Base model: gives every model access to the shared PDO connection
 * (config/database.php). Models contain data access only — business
 * rules live in services, request handling in controllers.
 */
abstract class Model
{
    protected static function db(): PDO
    {
        return db();
    }
}
