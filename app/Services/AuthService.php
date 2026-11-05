<?php

namespace App\Services;

use App\Models\User;
use App\Validators\LoginValidator;

/**
 * Authentication business logic: login, logout and access checks
 * (Module 1 — User & Authentication Management).
 */
class AuthService
{
    /** Per-request cache for user(); reset whenever the session changes. */
    private static bool $userLoaded = false;
    private static ?array $cachedUser = null;

    /**
     * Verify credentials and log the user in.
     * Flashes an error message and returns false on failure.
     */
    public static function attempt(string $email, string $password): bool
    {
        $validator = new LoginValidator(['email' => $email, 'password' => $password]);

        if ($validator->fails()) {
            flash('error', $validator->first('email') ?? $validator->first('password'));
            return false;
        }

        $user = User::findByEmail($email);

        // The same message is shown for an unknown email and a wrong
        // password, so the form does not reveal which one failed.
        if ($user === null || !password_verify($password, $user['password_hash'])) {
            flash('error', 'Invalid email or password.');
            return false;
        }

        if ((int) $user['is_active'] !== 1) {
            flash('error', 'This account has been deactivated. Please contact support.');
            return false;
        }

        session_regenerate_id(true);      // prevent session fixation
        $_SESSION['user_id'] = (int) $user['id'];
        self::$userLoaded = false;        // session changed — re-resolve user
        unset($_SESSION['_old']);

        activity('login');

        return true;
    }

    public static function logout(): void
    {
        activity('logout');
        $_SESSION = [];
        session_regenerate_id(true);
        self::$userLoaded = false;
    }

    /**
     * The logged-in user as a users row with `roles` attached, or null.
     * Cached per request; deactivated accounts lose access immediately.
     */
    public static function user(): ?array
    {
        if (self::$userLoaded) {
            return self::$cachedUser;
        }
        self::$userLoaded = true;

        if (empty($_SESSION['user_id'])) {
            return self::$cachedUser = null;
        }

        $user = User::find((int) $_SESSION['user_id']);

        if ($user === null || (int) $user['is_active'] !== 1) {
            $_SESSION = [];
            return self::$cachedUser = null;
        }

        $user['roles'] = User::roles((int) $user['id']);
        return self::$cachedUser = $user;
    }

    /** True when the logged-in user holds at least one of the roles. */
    public static function hasRole(string ...$roles): bool
    {
        $user = self::user();
        if ($user === null) {
            return false;
        }

        return count(array_intersect($roles, $user['roles'])) > 0;
    }
}
