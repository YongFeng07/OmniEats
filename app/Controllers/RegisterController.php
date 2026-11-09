<?php

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\UserService;
use App\Validators\RegisterValidator;

/**
 * Public registration (Module 1): customer accounts and restaurant
 * owner accounts. The restaurant profile itself (the tenant) is
 * created in Module 2 — registration here creates the account only.
 */
class RegisterController
{
    public function showCustomer(): void
    {
        view('auth/register', [
            'title'   => 'Create your customer account',
            'action'  => url('/register'),
            'heading' => 'Create your OmniEats account',
            'subtext' => 'Browse restaurants, order food and track your orders.',
            'isOwner' => false,
        ]);
    }

    public function showOwner(): void
    {
        view('auth/register', [
            'title'   => 'Register your restaurant account',
            'action'  => url('/register/restaurant'),
            'heading' => 'Start selling on OmniEats',
            'subtext' => 'Create your restaurant owner account, then register your restaurant — a Free plan subscription is set up automatically so you can start building your menu right away.',
            'isOwner' => true,
        ]);
    }

    public function customer(): void
    {
        $this->register('customer', '/register');
    }

    public function owner(): void
    {
        // Grab/Foodpanda merchant onboarding: right after sign-up the
        // owner lands DIRECTLY in the restaurant setup form.
        $this->register('restaurant_owner', '/register/restaurant', '/owner/restaurant/create');
    }

    private function register(string $role, string $backPath, ?string $successPath = null): void
    {
        $data = [
            'name'                  => trim((string) ($_POST['name'] ?? '')),
            'email'                 => trim((string) ($_POST['email'] ?? '')),
            'phone'                 => trim((string) ($_POST['phone'] ?? '')),
            'password'              => (string) ($_POST['password'] ?? ''),
            'password_confirmation' => (string) ($_POST['password_confirmation'] ?? ''),
        ];
        $_SESSION['_old'] = $data;

        $validator = new RegisterValidator($data);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            redirect($backPath);
        }

        if (!UserService::register($data, $role)) {
            redirect($backPath);
        }

        flash('success', 'Welcome to OmniEats! Your account has been created.');
        redirect($successPath ?? AuthMiddleware::homeFor(AuthService::user()));
    }
}
