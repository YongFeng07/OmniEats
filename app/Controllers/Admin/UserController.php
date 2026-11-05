<?php

namespace App\Controllers\Admin;

use App\Models\ActivityLog;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use App\Services\AuthService;
use App\Services\UserService;
use App\Validators\UserCreateValidator;
use App\Validators\UserUpdateValidator;

/**
 * Platform user management (Module 1) — super admin only (route guard).
 *
 * CRUD mapping: create = create(), retrieve = index()/show(),
 * update = edit()/update()/toggleStatus(), delete = soft deletion via
 * toggleStatus() (accounts are deactivated, never hard-deleted).
 */
class UserController
{
    public function index(): void
    {
        // Filters come from the GET form; role and status are whitelisted.
        $q      = trim((string) ($_GET['q'] ?? ''));
        $role   = in_array($_GET['role'] ?? '', Role::NAMES, true) ? $_GET['role'] : '';
        $status = in_array($_GET['status'] ?? '', ['active', 'inactive'], true) ? $_GET['status'] : '';

        view('admin/users/index', [
            'title' => 'User Management',
            'users' => User::search($q !== '' ? $q : null, $role !== '' ? $role : null, $status !== '' ? $status : null),
            'roles' => Role::all(),
            'q'     => $q,
            'fRole' => $role,
            'fStatus' => $status,
        ]);
    }

    public function create(): void
    {
        view('admin/users/create', [
            'title'       => 'New User',
            'roles'       => Role::all(),
            'restaurants' => Restaurant::all(),
        ]);
    }

    public function store(): void
    {
        $data = $this->formData();
        $_SESSION['_old'] = $data;

        $validator = new UserCreateValidator($data);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            redirect('/admin/users/create');
        }

        if (!UserService::adminCreate($data)) {
            redirect('/admin/users/create');
        }

        unset($_SESSION['_old']);
        flash('success', 'User account created.');
        redirect('/admin/users');
    }

    public function show(string $id): void
    {
        $user = User::find((int) $id);
        if ($user === null) {
            abort(404);
        }

        $user['roles'] = User::roles((int) $user['id']);

        view('admin/users/show', [
            'title'     => 'User Details',
            'user'      => $user,
            'restaurant'=> !empty($user['restaurant_id']) ? Restaurant::find((int) $user['restaurant_id']) : null,
            'activities'=> ActivityLog::latestForUser((int) $user['id']),
        ]);
    }

    public function edit(string $id): void
    {
        $user = User::find((int) $id);
        if ($user === null) {
            abort(404);
        }

        $roles = User::roles((int) $user['id']);

        view('admin/users/edit', [
            'title'       => 'Edit User',
            'user'        => $user,
            'userRole'    => $roles[0] ?? '',
            'roles'       => Role::all(),
            'restaurants' => Restaurant::all(),
        ]);
    }

    public function update(string $id): void
    {
        $data = $this->formData();
        $_SESSION['_old'] = $data;

        $validator = new UserUpdateValidator($data);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            redirect('/admin/users/' . (int) $id . '/edit');
        }

        if (!UserService::adminUpdate((int) $id, $data, AuthService::user())) {
            redirect('/admin/users/' . (int) $id . '/edit');
        }

        unset($_SESSION['_old']);
        flash('success', 'User updated.');
        redirect('/admin/users/' . (int) $id);
    }

    /** Soft delete / restore (Module 1 DELETE via deactivation). */
    public function toggleStatus(string $id): void
    {
        if (!UserService::toggleStatus((int) $id, AuthService::user())) {
            redirect('/admin/users/' . (int) $id);
        }

        flash('success', 'Account status updated.');
        redirect('/admin/users/' . (int) $id);
    }

    /** Shared input collection for create/update forms. */
    private function formData(): array
    {
        return [
            'name'                  => trim((string) ($_POST['name'] ?? '')),
            'email'                 => trim((string) ($_POST['email'] ?? '')),
            'phone'                 => trim((string) ($_POST['phone'] ?? '')),
            'role'                  => (string) ($_POST['role'] ?? ''),
            'restaurant_id'         => (string) ($_POST['restaurant_id'] ?? ''),
            'is_active'             => (string) ($_POST['is_active'] ?? '1'),
            'password'              => (string) ($_POST['password'] ?? ''),
            'password_confirmation' => (string) ($_POST['password_confirmation'] ?? ''),
        ];
    }
}
