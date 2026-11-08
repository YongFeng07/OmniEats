<?php

namespace App\Controllers;

use App\Models\UserAddress;
use App\Services\AuthService;
use App\Services\UserService;
use App\Validators\PasswordValidator;
use App\Validators\ProfileValidator;

/**
 * Own profile, password and address book management (Module 1) —
 * available to every logged-in user, regardless of role.
 */
class ProfileController
{
    public function show(): void
    {
        view('profile/index', [
            'title'     => 'My Profile',
            'user'      => AuthService::user(),
            'addresses' => UserAddress::allForUser((int) AuthService::user()['id']),
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

    /** Address book (customer convenience, Grab-style saved addresses). */
    public function addAddress(): void
    {
        $label = trim((string) ($_POST['label'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $isDefault = isset($_POST['is_default']) && $_POST['is_default'] === '1';

        if ($label === '' || $address === '' || $phone === '') {
            flash('error', 'Label, address and phone are required for a saved address.');
            redirect('/profile');
        }
        if (mb_strlen($label) > 50 || mb_strlen($address) > 255) {
            flash('error', 'The label or address is too long.');
            redirect('/profile');
        }
        if (!preg_match('/^[0-9+\-() ]{8,20}$/', $phone)) {
            flash('error', 'Please enter a valid phone number (8-20 characters).');
            redirect('/profile');
        }

        UserAddress::create((int) AuthService::user()['id'], $label, $address, $phone, $isDefault);
        flash('success', 'Address saved — it will appear in the checkout picker.');
        redirect('/profile');
    }

    public function deleteAddress(string $id): void
    {
        $userId = (int) AuthService::user()['id'];
        $address = UserAddress::find((int) $id);
        if ($address === null || (int) $address['user_id'] !== $userId) {
            flash('error', 'Address not found.');
            redirect('/profile');
        }

        UserAddress::delete((int) $id, $userId);
        flash('success', 'Address deleted.');
        redirect('/profile');
    }

    public function defaultAddress(string $id): void
    {
        $userId = (int) AuthService::user()['id'];
        $address = UserAddress::find((int) $id);
        if ($address === null || (int) $address['user_id'] !== $userId) {
            flash('error', 'Address not found.');
            redirect('/profile');
        }

        UserAddress::setDefault((int) $id, $userId);
        flash('success', 'Default address updated.');
        redirect('/profile');
    }
}
