<?php

namespace App\Controllers\Owner;

use App\Models\Restaurant;
use App\Services\AuthService;
use App\Services\RestaurantService;
use App\Validators\RestaurantValidator;

/**
 * Owner's own restaurant management (Module 2). The tenant always comes
 * from the session (users.restaurant_id) — there is no id in the URL to
 * tamper with.
 */
class RestaurantController
{
    /** The logged-in owner's restaurant, or null. */
    private function tenant(): ?array
    {
        $restaurantId = AuthService::user()['restaurant_id'] ?? null;
        if ($restaurantId === null) {
            return null;
        }
        return Restaurant::find((int) $restaurantId);
    }

    public function create(): void
    {
        if ($this->tenant() !== null) {
            redirect('/owner/restaurant/edit');
        }

        view('owner/restaurant/form', [
            'title'      => 'Register your restaurant',
            'heading'    => 'Register your restaurant',
            'subtext'    => 'Create your restaurant profile. It becomes visible to customers after platform approval.',
            'action'     => url('/owner/restaurant/create'),
            'restaurant' => null,
        ]);
    }

    public function store(): void
    {
        $data = $this->formData();
        $_SESSION['_old'] = $data;

        $validator = new RestaurantValidator($data);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            redirect('/owner/restaurant/create');
        }

        if (!RestaurantService::registerForOwner($data, $_FILES, (int) AuthService::user()['id'])) {
            redirect('/owner/restaurant/create');
        }

        unset($_SESSION['_old']);
        flash('success', 'Restaurant registered! It is now pending platform approval — you can already manage it from your dashboard.');
        redirect('/owner');
    }

    public function edit(): void
    {
        $restaurant = $this->tenant();
        if ($restaurant === null) {
            redirect('/owner/restaurant/create');
        }

        view('owner/restaurant/form', [
            'title'      => 'Restaurant profile',
            'heading'    => 'Edit restaurant profile',
            'subtext'    => 'Customers see this information on your public restaurant page.',
            'action'     => url('/owner/restaurant/edit'),
            'restaurant' => $restaurant,
        ]);
    }

    public function update(): void
    {
        $restaurant = $this->tenant();
        if ($restaurant === null) {
            redirect('/owner/restaurant/create');
        }

        $data = $this->formData();
        $_SESSION['_old'] = $data;

        $validator = new RestaurantValidator($data);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            redirect('/owner/restaurant/edit');
        }

        if (!RestaurantService::updateProfile((int) $restaurant['id'], $data, $_FILES)) {
            redirect('/owner/restaurant/edit');
        }

        unset($_SESSION['_old']);
        flash('success', 'Restaurant profile updated.');
        redirect('/owner/restaurant/edit');
    }

    /** ★ UNIQUE FEATURE — OPEN/CLOSED availability toggle. */
    public function toggleOpen(): void
    {
        $restaurant = $this->tenant();
        if ($restaurant === null) {
            redirect('/owner/restaurant/create');
        }

        if (!RestaurantService::toggleOpen((int) $restaurant['id'])) {
            redirect('/owner');
        }

        $updated = Restaurant::find((int) $restaurant['id']);
        flash(
            'success',
            (int) $updated['is_open'] === 1
                ? 'Your restaurant is now OPEN and accepting new orders.'
                : 'Your restaurant is now CLOSED. Customers can still view your profile, but new orders are blocked.'
        );
        redirect('/owner');
    }

    /** Auto-accept toggle: paid orders skip the manual Accept step. */
    public function toggleAutoAccept(): void
    {
        $restaurant = $this->tenant();
        if ($restaurant === null) {
            redirect('/owner/restaurant/create');
        }

        $auto = (int) $restaurant['auto_accept'] !== 1;
        Restaurant::setAutoAccept((int) $restaurant['id'], $auto);
        activity($auto ? 'auto_accept_on' : 'auto_accept_off', 'restaurant', (int) $restaurant['id']);

        flash('success', $auto
            ? 'Auto-accept is ON — paid orders now go straight to preparation.'
            : 'Auto-accept is OFF — you will accept each paid order manually.');
        redirect('/owner');
    }

    private function formData(): array
    {
        return [
            'name'             => trim((string) ($_POST['name'] ?? '')),
            'description'      => trim((string) ($_POST['description'] ?? '')),
            'phone'            => trim((string) ($_POST['phone'] ?? '')),
            'email'            => trim((string) ($_POST['email'] ?? '')),
            'address'          => trim((string) ($_POST['address'] ?? '')),
            'city'             => trim((string) ($_POST['city'] ?? '')),
            'state'            => trim((string) ($_POST['state'] ?? '')),
            'postcode'         => trim((string) ($_POST['postcode'] ?? '')),
            'opening_hours'    => trim((string) ($_POST['opening_hours'] ?? '')),
            'delivery_minutes' => trim((string) ($_POST['delivery_minutes'] ?? '')),
            'free_delivery'    => isset($_POST['free_delivery']) && $_POST['free_delivery'] === '1' ? '1' : '0',
        ];
    }
}
