<?php

namespace App\Models;

/**
 * Data access for the restaurants table. Module 2 (Restaurant & Branch
 * Management) extends this model with its full CRUD operations.
 */
class Restaurant extends Model
{
    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM restaurants WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }
}
