<?php

namespace App\Controllers\Admin;

use App\Models\RiderProfile;
use App\Services\RiderService;
use App\Validators\UserCreateValidator;

/**
 * Rider account management — Super Admin only (Delivery & Rider
 * module, Student 3). Rider accounts are NEVER self-registered.
 * Delete = suspend (records preserved); reactivation is supported.
 */
class RiderController
{
    public function index(): void
    {
        view('admin/riders/index', [
            'title'  => 'Riders',
            'riders' => RiderProfile::all(),
        ]);
    }

    public function create(): void
    {
        view('admin/riders/form', [
            'title'  => 'Register a rider',
            'action' => url('/admin/riders/create'),
            'rider'  => null,
        ]);
    }

    public function store(): void
    {
        $data = [
            'name'     => trim((string) ($_POST['name'] ?? '')),
            'email'    => trim((string) ($_POST['email'] ?? '')),
            'phone'    => trim((string) ($_POST['phone'] ?? '')),
            'password' => (string) ($_POST['password'] ?? ''),
            'password_confirmation' => (string) ($_POST['password_confirmation'] ?? ''),
            'role'     => 'rider',
            'restaurant_id' => '',
            'vehicle_type'  => trim((string) ($_POST['vehicle_type'] ?? '')),
            'vehicle_plate' => trim((string) ($_POST['vehicle_plate'] ?? '')),
        ];
        $_SESSION['_old'] = $data;

        $validator = new UserCreateValidator($data);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            redirect('/admin/riders/create');
        }

        if (!RiderService::createRider($data)) {
            redirect('/admin/riders/create');
        }

        unset($_SESSION['_old']);
        flash('success', 'Rider account created. The rider can now sign in to the Rider Portal.');
        redirect('/admin/riders');
    }

    public function edit(string $id): void
    {
        $rider = RiderProfile::find((int) $id);
        if ($rider === null) {
            abort(404);
        }

        view('admin/riders/form', [
            'title'  => 'Edit rider',
            'action' => url('/admin/riders/' . (int) $id . '/edit'),
            'rider'  => $rider,
        ]);
    }

    public function update(string $id): void
    {
        $rider = RiderProfile::find((int) $id);
        if ($rider === null) {
            abort(404);
        }

        $vehicleType = trim((string) ($_POST['vehicle_type'] ?? ''));
        $vehiclePlate = trim((string) ($_POST['vehicle_plate'] ?? ''));

        RiderProfile::update((int) $id, $vehicleType, $vehiclePlate);
        activity('update_rider', 'rider_profile', (int) $id);

        flash('success', 'Rider details updated.');
        redirect('/admin/riders');
    }

    /** Suspend / reactivate (rider DELETE — audit records preserved). */
    public function toggleActive(string $id): void
    {
        $rider = RiderProfile::find((int) $id);
        if ($rider === null) {
            abort(404);
        }

        $suspended = (int) $rider['is_active'] === 1;
        RiderService::setSuspended((int) $id, $suspended);

        flash('success', $suspended
            ? 'Rider suspended — pending assignments were returned to the pool.'
            : 'Rider reactivated.');
        redirect('/admin/riders');
    }

    /** Admin also controls availability (spec: update availability). */
    public function toggleAvailable(string $id): void
    {
        $rider = RiderProfile::find((int) $id);
        if ($rider === null) {
            abort(404);
        }

        $available = (int) $rider['is_available'] !== 1;
        RiderService::setAvailability((int) $id, $available);
        activity($available ? 'rider_available' : 'rider_unavailable', 'rider_profile', (int) $id);

        flash('success', $available ? 'Rider marked available.' : 'Rider marked unavailable.');
        redirect('/admin/riders');
    }
}
