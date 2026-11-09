<?php

namespace App\Middleware;

use App\Services\AuthService;

/**
 * Route guards (RBAC), declared per-route in routes/web.php:
 *
 *   'guest'            — only when NOT logged in
 *   'auth'             — any logged-in user
 *   'roles:admin,owner' — one of the listed roles
 *
 * Security note: these guards are the FIRST line of defence. Controllers
 * additionally re-verify ownership (tenant) rules for every protected
 * record — hiding a button in the view is never the only protection.
 */
class AuthMiddleware
{
    public static function apply(string $guard): void
    {
        if ($guard === 'guest') {
            if (AuthService::user() !== null) {
                redirect(self::homeFor(AuthService::user()));
            }
            return;
        }

        if ($guard === 'auth') {
            if (AuthService::user() === null) {
                redirect('/login');
            }
            return;
        }

        if (str_starts_with($guard, 'roles:')) {
            // Not logged in? Send to the sign-in page first.
            if (AuthService::user() === null) {
                redirect('/login');
            }

            $roles = explode(',', substr($guard, 6));
            if (!AuthService::hasRole(...$roles)) {
                abort(403, 'You do not have permission to access this page.');
            }
            return;
        }

        abort(500, 'Unknown route guard: ' . $guard);
    }

    /** The dashboard a logged-in user lands on. */
    public static function homeFor(array $user): string
    {
        $map = [
            'super_admin'      => '/admin',
            'restaurant_owner' => '/owner',
            'restaurant_staff' => '/owner',
            'rider'            => '/rider',
            'customer'         => '/customer',
        ];

        foreach ($map as $role => $path) {
            if (in_array($role, $user['roles'], true)) {
                return $path;
            }
        }

        return '/';
    }
}
