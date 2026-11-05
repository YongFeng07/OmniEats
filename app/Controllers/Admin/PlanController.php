<?php

namespace App\Controllers\Admin;

use App\Models\Plan;
use App\Services\PlanService;
use App\Validators\PlanValidator;

/**
 * Subscription plan management (Module 4) — super admin only.
 * CRUD mapping: create = create()/store(), retrieve = index(),
 * update = edit()/update(), delete = soft archive via toggleActive().
 */
class PlanController
{
    public function index(): void
    {
        view('admin/plans/index', [
            'title' => 'Subscription Plans',
            'plans' => Plan::adminListing(),
        ]);
    }

    public function create(): void
    {
        view('admin/plans/form', [
            'title'  => 'New plan',
            'heading'=> 'Create a subscription plan',
            'action' => url('/admin/plans/create'),
            'plan'   => null,
        ]);
    }

    public function store(): void
    {
        $data = $this->formData();
        $_SESSION['_old'] = $data;

        $validator = new PlanValidator($data);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            redirect('/admin/plans/create');
        }

        if (!PlanService::create($data)) {
            redirect('/admin/plans/create');
        }

        unset($_SESSION['_old']);
        flash('success', 'Plan created.');
        redirect('/admin/plans');
    }

    public function edit(string $id): void
    {
        $plan = Plan::find((int) $id);
        if ($plan === null) {
            abort(404);
        }

        view('admin/plans/form', [
            'title'  => 'Edit plan',
            'heading'=> 'Edit plan — ' . $plan['name'],
            'action' => url('/admin/plans/' . (int) $plan['id'] . '/edit'),
            'plan'   => $plan,
        ]);
    }

    public function update(string $id): void
    {
        $data = $this->formData();
        $_SESSION['_old'] = $data;

        $validator = new PlanValidator($data);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            redirect('/admin/plans/' . (int) $id . '/edit');
        }

        if (!PlanService::update((int) $id, $data)) {
            redirect('/admin/plans/' . (int) $id . '/edit');
        }

        unset($_SESSION['_old']);
        flash('success', 'Plan updated.');
        redirect('/admin/plans');
    }

    /** Archive or restore a plan (Module 4 DELETE via soft archive). */
    public function toggleActive(string $id): void
    {
        if (!PlanService::toggleActive((int) $id)) {
            redirect('/admin/plans');
        }

        flash('success', 'Plan status updated.');
        redirect('/admin/plans');
    }

    private function formData(): array
    {
        return [
            'name'          => trim((string) ($_POST['name'] ?? '')),
            'price'         => trim((string) ($_POST['price'] ?? '')),
            'max_branches'  => trim((string) ($_POST['max_branches'] ?? '')),
            'max_food_items'=> trim((string) ($_POST['max_food_items'] ?? '')),
            'description'   => trim((string) ($_POST['description'] ?? '')),
            'features'      => trim((string) ($_POST['features'] ?? '')),
            'is_active'     => (string) ($_POST['is_active'] ?? '1'),
            'sort_order'    => trim((string) ($_POST['sort_order'] ?? '0')),
        ];
    }
}
