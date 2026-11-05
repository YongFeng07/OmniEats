<?php

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AuthService;

/**
 * Login and logout (Module 1). Registration is implemented with the
 * rest of Module 1 in Phase 5.
 */
class AuthController
{
    public function showLogin(): void
    {
        view('auth/login', ['title' => 'Sign in']);
    }

    public function login(): void
    {
        // Keep the submitted email so the form can repopulate it.
        $_SESSION['_old'] = [
            'email' => (string) ($_POST['email'] ?? ''),
        ];

        if (AuthService::attempt(
            (string) ($_POST['email'] ?? ''),
            (string) ($_POST['password'] ?? '')
        )) {
            redirect(AuthMiddleware::homeFor(AuthService::user()));
        }

        redirect('/login');
    }

    public function logout(): void
    {
        AuthService::logout();
        flash('success', 'You have been signed out.');
        redirect('/');
    }
}
