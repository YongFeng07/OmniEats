<?php

namespace App\Controllers\Owner;

use App\Models\Branch;
use App\Services\AuthService;
use App\Services\BranchService;
use App\Validators\BranchValidator;

/**
 * Branch management (Module 2). All operations are scoped to the
 * tenant from the session; cross-tenant branch ids are rejected in
 * BranchService (reported as "not found").
 */
class BranchController
{
    private function tenantId(): ?int
    {
        $restaurantId = AuthService::user()['restaurant_id'] ?? null;
        return $restaurantId !== null ? (int) $restaurantId : null;
    }

    public function index(): void
    {
        $restaurantId = $this->tenantId();
        if ($restaurantId === null) {
            redirect('/owner');
        }

        view('owner/branches/index', [
            'title'    => 'Branches',
            'branches' => Branch::allForRestaurant($restaurantId),
        ]);
    }

    public function create(): void
    {
        if ($this->tenantId() === null) {
            redirect('/owner');
        }

        view('owner/branches/form', [
            'title'   => 'New branch',
            'heading' => 'Add a branch',
            'action'  => url('/owner/branches/create'),
            'branch'  => null,
        ]);
    }

    public function store(): void
    {
        $restaurantId = $this->tenantId();
        if ($restaurantId === null) {
            redirect('/owner');
        }

        $data = $this->formData();
        $_SESSION['_old'] = $data;

        $validator = new BranchValidator($data);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            redirect('/owner/branches/create');
        }

        BranchService::create($restaurantId, $data);

        unset($_SESSION['_old']);
        flash('success', 'Branch added.');
        redirect('/owner/branches');
    }

    public function edit(string $id): void
    {
        $restaurantId = $this->tenantId();
        if ($restaurantId === null) {
            redirect('/owner');
        }

        $branch = Branch::find((int) $id);
        if ($branch === null || (int) $branch['restaurant_id'] !== $restaurantId) {
            abort(404);
        }

        view('owner/branches/form', [
            'title'   => 'Edit branch',
            'heading' => 'Edit branch — ' . $branch['name'],
            'action'  => url('/owner/branches/' . (int) $branch['id'] . '/edit'),
            'branch'  => $branch,
        ]);
    }

    public function update(string $id): void
    {
        $restaurantId = $this->tenantId();
        if ($restaurantId === null) {
            redirect('/owner');
        }

        $data = $this->formData();
        $_SESSION['_old'] = $data;

        $validator = new BranchValidator($data);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            redirect('/owner/branches/' . (int) $id . '/edit');
        }

        if (!BranchService::update($restaurantId, (int) $id, $data)) {
            redirect('/owner/branches');
        }

        unset($_SESSION['_old']);
        flash('success', 'Branch updated.');
        redirect('/owner/branches');
    }

    public function delete(string $id): void
    {
        $restaurantId = $this->tenantId();
        if ($restaurantId === null) {
            redirect('/owner');
        }

        if (!BranchService::delete($restaurantId, (int) $id)) {
            redirect('/owner/branches');
        }

        flash('success', 'Branch deleted.');
        redirect('/owner/branches');
    }

    private function formData(): array
    {
        return [
            'name'          => trim((string) ($_POST['name'] ?? '')),
            'address'       => trim((string) ($_POST['address'] ?? '')),
            'phone'         => trim((string) ($_POST['phone'] ?? '')),
            'opening_hours' => trim((string) ($_POST['opening_hours'] ?? '')),
        ];
    }
}
