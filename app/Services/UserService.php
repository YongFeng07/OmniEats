<?php

namespace App\Services;

use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;

/**
 * User account business logic (Module 1 — User & Authentication).
 *
 * Every method returns true/false and flashes the user-facing message;
 * controllers stay thin: validate → call service → redirect.
 */
class UserService
{
    /**
     * Public self-registration for a fixed role (customer or restaurant
     * owner). The submitted form never chooses a role freely, which is
     * the main guard against role escalation.
     */
    public static function register(array $data, string $roleName): bool
    {
        if (User::findByEmail($data['email']) !== null) {
            flash('error', 'An account with this email already exists.');
            return false;
        }

        $role = Role::findByName($roleName);
        if ($role === null) {
            flash('error', 'Unknown account type.');
            return false;
        }

        $id = User::create(
            $data['name'],
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['phone'] !== '' ? $data['phone'] : null,
            null
        );
        User::setRoles($id, [(int) $role['id']]);

        // Auto sign-in (session set first so the activity log carries
        // the new user's id).
        AuthService::loginUser(['id' => $id]);
        activity('register', 'user', $id, "role={$roleName}");

        return true;
    }

    /**
     * Admin creates a user account. Owners/staff must be linked to an
     * existing restaurant tenant (validated here, not in the browser).
     */
    public static function adminCreate(array $data): bool
    {
        if (User::findByEmail($data['email']) !== null) {
            flash('error', 'An account with this email already exists.');
            return false;
        }

        $role = Role::findByName($data['role']);
        if ($role === null) {
            flash('error', 'Please choose a valid role.');
            return false;
        }

        $restaurantId = in_array($data['role'], ['restaurant_owner', 'restaurant_staff'], true)
            ? (int) $data['restaurant_id']
            : null;

        if ($restaurantId !== null && Restaurant::find($restaurantId) === null) {
            flash('error', 'Please choose a valid restaurant.');
            return false;
        }

        $id = User::create(
            $data['name'],
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['phone'] !== '' ? $data['phone'] : null,
            $restaurantId
        );
        User::setRoles($id, [(int) $role['id']]);

        activity('create_user', 'user', $id, "role={$data['role']}");

        return true;
    }

    /**
     * Admin updates a user: profile fields, role, tenant link, status,
     * and optionally a new password. Includes self-protection rules.
     */
    public static function adminUpdate(int $userId, array $data, array $admin): bool
    {
        $target = User::find($userId);
        if ($target === null) {
            flash('error', 'User not found.');
            return false;
        }

        $existing = User::findByEmail($data['email']);
        if ($existing !== null && (int) $existing['id'] !== $userId) {
            flash('error', 'An account with this email already exists.');
            return false;
        }

        $role = Role::findByName($data['role']);
        if ($role === null) {
            flash('error', 'Please choose a valid role.');
            return false;
        }

        $isSelf = (int) $admin['id'] === $userId;

        // An administrator must not lock themselves out of the platform.
        if ($isSelf) {
            if ($data['is_active'] === '0') {
                flash('error', 'You cannot deactivate your own account.');
                return false;
            }
            if ($data['role'] !== 'super_admin') {
                flash('error', 'You cannot remove your own super admin role.');
                return false;
            }
        }

        $restaurantId = in_array($data['role'], ['restaurant_owner', 'restaurant_staff'], true)
            ? (int) $data['restaurant_id']
            : null;

        if ($restaurantId !== null && Restaurant::find($restaurantId) === null) {
            flash('error', 'Please choose a valid restaurant.');
            return false;
        }

        User::update($userId, $data['name'], $data['email'], $data['phone'] !== '' ? $data['phone'] : null);
        User::setRoles($userId, [(int) $role['id']]);
        User::setTenant($userId, $restaurantId);
        User::setStatus($userId, $data['is_active'] === '1');

        if (($data['password'] ?? '') !== '') {
            User::updatePassword($userId, password_hash($data['password'], PASSWORD_DEFAULT));
        }

        activity('update_user', 'user', $userId, "role={$data['role']}");

        return true;
    }

    /** Soft delete / restore (toggle is_active). */
    public static function toggleStatus(int $userId, array $admin): bool
    {
        if ((int) $admin['id'] === $userId) {
            flash('error', 'You cannot deactivate your own account.');
            return false;
        }

        $target = User::find($userId);
        if ($target === null) {
            flash('error', 'User not found.');
            return false;
        }

        $activate = (int) $target['is_active'] !== 1;
        User::setStatus($userId, $activate);

        activity($activate ? 'activate_user' : 'deactivate_user', 'user', $userId);

        return true;
    }

    /** Own profile update (name, email, phone). */
    public static function updateProfile(int $userId, array $data): bool
    {
        $existing = User::findByEmail($data['email']);
        if ($existing !== null && (int) $existing['id'] !== $userId) {
            flash('error', 'An account with this email already exists.');
            return false;
        }

        User::update($userId, $data['name'], $data['email'], $data['phone'] !== '' ? $data['phone'] : null);

        activity('update_profile', 'user', $userId);

        return true;
    }

    /** Change own password (current password must be verified first). */
    public static function changePassword(int $userId, array $data): bool
    {
        $user = User::find($userId);

        if ($user === null || !password_verify($data['current_password'], $user['password_hash'])) {
            flash('error', 'Your current password is incorrect.');
            return false;
        }

        if ($data['new_password'] === $data['current_password']) {
            flash('error', 'New password must be different from the current one.');
            return false;
        }

        User::updatePassword($userId, password_hash($data['new_password'], PASSWORD_DEFAULT));

        activity('change_password', 'user', $userId);

        return true;
    }
}
