<?php

namespace App\Controllers\Owner;

use App\Models\Category;
use App\Services\AuthService;
use App\Services\CategoryService;
use App\Validators\CategoryValidator;

/**
 * Food category management (Module 3) — owner and staff, scoped to the
 * tenant from the session.
 */
class CategoryController
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

        view('owner/categories/index', [
            'title'      => 'Menu categories',
            'categories' => Category::allForRestaurant($restaurantId),
        ]);
    }

    public function create(): void
    {
        if ($this->tenantId() === null) {
            redirect('/owner');
        }

        view('owner/categories/form', [
            'title'       => 'New category',
            'heading'     => 'Add a category',
            'action'      => url('/owner/categories/create'),
            'category'    => null,
            'suggestions' => Category::distinctAcrossPlatform(),
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

        $validator = new CategoryValidator($data);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            redirect('/owner/categories/create');
        }

        if (!CategoryService::create($restaurantId, $data)) {
            redirect('/owner/categories/create');
        }

        unset($_SESSION['_old']);
        flash('success', 'Category added.');
        redirect('/owner/categories');
    }

    public function edit(string $id): void
    {
        $restaurantId = $this->tenantId();
        if ($restaurantId === null) {
            redirect('/owner');
        }

        $category = Category::find((int) $id);
        if ($category === null || (int) $category['restaurant_id'] !== $restaurantId) {
            abort(404);
        }

        view('owner/categories/form', [
            'title'       => 'Edit category',
            'heading'     => 'Edit category — ' . $category['name'],
            'action'      => url('/owner/categories/' . (int) $category['id'] . '/edit'),
            'category'    => $category,
            'suggestions' => Category::distinctAcrossPlatform(),
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

        $validator = new CategoryValidator($data);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            redirect('/owner/categories/' . (int) $id . '/edit');
        }

        if (!CategoryService::update($restaurantId, (int) $id, $data)) {
            redirect('/owner/categories');
        }

        unset($_SESSION['_old']);
        flash('success', 'Category updated.');
        redirect('/owner/categories');
    }

    public function delete(string $id): void
    {
        $restaurantId = $this->tenantId();
        if ($restaurantId === null) {
            redirect('/owner');
        }

        if (!CategoryService::delete($restaurantId, (int) $id)) {
            redirect('/owner/categories');
        }

        flash('success', 'Category deleted.');
        redirect('/owner/categories');
    }

    private function formData(): array
    {
        return [
            'name'        => trim((string) ($_POST['name'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'sort_order'  => trim((string) ($_POST['sort_order'] ?? '0')),
        ];
    }
}
