<?php

namespace App\Controllers;

use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserFavourite;
use App\Services\AuthService;
use App\Services\UploadService;
use App\Services\UserService;
use App\Validators\PasswordValidator;
use App\Validators\ProfileValidator;

/**
 * Own profile, password and address book management (Module 1) —
 * available to every logged-in user, regardless of role.
 * Shopee-style profile: avatar, account stats, reviews, activity.
 */
class ProfileController
{
    public function show(): void
    {
        $user = AuthService::user();
        $userId = (int) $user['id'];

        $orders = Order::listForCustomer($userId);
        $completed = 0;
        foreach ($orders as $order) {
            if ($order['status'] === 'completed') {
                $completed++;
            }
        }

        view('profile/index', [
            'title'      => 'My Profile',
            'user'       => $user,
            'addresses'  => UserAddress::allForUser($userId),
            'stats'      => [
                'orders'     => count($orders),
                'completed'  => $completed,
                'reviews'    => count(Review::listForCustomer($userId)),
                'favourites' => count(UserFavourite::restaurantsForUser($userId)),
                'addresses'  => count(UserAddress::allForUser($userId)),
            ],
            'myReviews'  => Review::listForCustomer($userId),
            'activity'   => ActivityLog::latestForUser($userId, 6),
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

        // Optional avatar upload (Shopee-style profile picture).
        $avatar = UploadService::store($_FILES['avatar'] ?? [], 'avatars');
        if (!$avatar['ok']) {
            redirect('/profile');
        }
        if ($avatar['path'] !== null) {
            User::setAvatar((int) AuthService::user()['id'], $avatar['path']);
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

    /** Address book (Customer Profile module — Student 2). */
    public function addAddress(): void
    {
        $label = trim((string) ($_POST['label'] ?? ''));
        $recipient = trim((string) ($_POST['recipient_name'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));
        $city = trim((string) ($_POST['city'] ?? ''));
        $state = trim((string) ($_POST['state'] ?? ''));
        $postcode = trim((string) ($_POST['postcode'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $isDefault = isset($_POST['is_default']) && $_POST['is_default'] === '1';

        if ($label === '' || $recipient === '' || $address === '' || $phone === '') {
            flash('error', 'Label, recipient, address and phone are required for a saved address.');
            redirect('/profile');
        }
        if (mb_strlen($label) > 50 || mb_strlen($recipient) > 100 || mb_strlen($address) > 255) {
            flash('error', 'The label, recipient or address is too long.');
            redirect('/profile');
        }
        if (!preg_match('/^[0-9+\-() ]{8,20}$/', $phone)) {
            flash('error', 'Please enter a valid phone number (8-20 characters).');
            redirect('/profile');
        }
        if ($postcode !== '' && !preg_match('/^[0-9]{5}$/', $postcode)) {
            flash('error', 'Postcode must be exactly 5 digits.');
            redirect('/profile');
        }

        UserAddress::create(
            (int) AuthService::user()['id'],
            $label,
            $recipient,
            $address,
            $city,
            $state,
            $postcode,
            $phone,
            $isDefault
        );
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

    /** UPDATE (Customer Profile CRUD) — edit a saved address. */
    public function editAddress(string $id): void
    {
        $userId = (int) AuthService::user()['id'];
        $address = UserAddress::find((int) $id);
        if ($address === null || (int) $address['user_id'] !== $userId) {
            flash('error', 'Address not found.');
            redirect('/profile');
        }

        view('profile/address_form', [
            'title'   => 'Edit address',
            'action'  => url('/profile/addresses/' . (int) $id . '/edit'),
            'address' => $address,
        ]);
    }

    public function updateAddress(string $id): void
    {
        $userId = (int) AuthService::user()['id'];
        $address = UserAddress::find((int) $id);
        if ($address === null || (int) $address['user_id'] !== $userId) {
            flash('error', 'Address not found.');
            redirect('/profile');
        }

        $label = trim((string) ($_POST['label'] ?? ''));
        $recipient = trim((string) ($_POST['recipient_name'] ?? ''));
        $line = trim((string) ($_POST['address'] ?? ''));
        $city = trim((string) ($_POST['city'] ?? ''));
        $state = trim((string) ($_POST['state'] ?? ''));
        $postcode = trim((string) ($_POST['postcode'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));

        if ($label === '' || $recipient === '' || $line === '' || $phone === '') {
            flash('error', 'Label, recipient, address and phone are required.');
            redirect('/profile/addresses/' . (int) $id . '/edit');
        }
        if (mb_strlen($label) > 50 || mb_strlen($recipient) > 100 || mb_strlen($line) > 255) {
            flash('error', 'The label, recipient or address is too long.');
            redirect('/profile/addresses/' . (int) $id . '/edit');
        }
        if (!preg_match('/^[0-9+\-() ]{8,20}$/', $phone)) {
            flash('error', 'Please enter a valid phone number (8-20 characters).');
            redirect('/profile/addresses/' . (int) $id . '/edit');
        }
        if ($postcode !== '' && !preg_match('/^[0-9]{5}$/', $postcode)) {
            flash('error', 'Postcode must be exactly 5 digits.');
            redirect('/profile/addresses/' . (int) $id . '/edit');
        }

        UserAddress::update((int) $id, $userId, $label, $recipient, $line, $city, $state, $postcode, $phone);
        flash('success', 'Address updated.');
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
