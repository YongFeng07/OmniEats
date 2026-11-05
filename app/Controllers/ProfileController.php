<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\UserService;
use App\Validators\PasswordValidator;
use App\Validators\ProfileValidator;

/**
 * Own profile and password management (Module 1) — available to every
 * logged-in user, regardless of role.
 */
class ProfileController
{
    public function show(): void
    {
        view('profile/index', [
            'title' => 'My Profile',
            'user'  => AuthService::user(),
        ]);
    }

    public function update(): void
    {
        $data = [
            'name'  => trim((string) ($_POST['name'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
        ];
        $_SESSION['_old'] = $data;

        $validator = new ProfileValidator($data);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            redirect('/profile');
        }

        if (!UserService::updateProfile((int) AuthService::user()['id'], $data)) {
            redirect('/profile');
        }

        unset($_SESSION['_old']);
        flash('success', 'Your profile has been updated.');
        redirect('/profile');
    }

    public function showPassword(): void
    {
        view('profile/password', ['title' => 'Change Password']);
    }

    public function changePassword(): void
    {
        $data = [
            'current_password'          => (string) ($_POST['current_password'] ?? ''),
            'new_password'              => (string) ($_POST['new_password'] ?? ''),
            'new_password_confirmation' => (string) ($_POST['new_password_confirmation'] ?? ''),
        ];
        $_SESSION['_old'] = []; // passwords are never repopulated

        $validator = new PasswordValidator($data);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            redirect('/profile/password');
        }

        if (!UserService::changePassword((int) AuthService::user()['id'], $data)) {
            redirect('/profile/password');
        }

        flash('success', 'Your password has been changed.');
        redirect('/profile/password');
    }
}
