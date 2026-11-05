<?php

namespace App\Controllers\Owner;

use App\Models\Category;
use App\Models\FoodItem;
use App\Services\AuthService;
use App\Services\FoodItemService;
use App\Validators\FoodItemValidator;

/**
 * Food item management (Module 3) — owner and staff, scoped to the
 * tenant from the session. Search and category filters are server-side.
 */
class FoodItemController
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

        $q = trim((string) ($_GET['q'] ?? ''));
        $categories = Category::allForRestaurant($restaurantId);

        // Category filter must be one of THIS restaurant's categories.
        $categoryId = (int) ($_GET['category'] ?? 0);
        $validIds = array_map(fn (array $c): int => (int) $c['id'], $categories);
        if ($categoryId > 0 && !in_array($categoryId, $validIds, true)) {
            $categoryId = 0;
        }

        view('owner/foods/index', [
            'title'      => 'Food items',
            'items'      => FoodItem::menuForRestaurant(
                $restaurantId,
                $q !== '' ? $q : null,
                $categoryId > 0 ? $categoryId : null
            ),
            'categories' => $categories,
            'q'          => $q,
            'fCategory'  => $categoryId,
        ]);
    }

    public function create(): void
    {
        $restaurantId = $this->tenantId();
        if ($restaurantId === null) {
            redirect('/owner');
        }

        view('owner/foods/form', [
            'title'      => 'New food item',
            'heading'    => 'Add a food item',
            'action'     => url('/owner/foods/create'),
            'categories' => Category::allForRestaurant($restaurantId),
            'item'       => null,
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

        $validator = new FoodItemValidator($data);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            redirect('/owner/foods/create');
        }

        if (!FoodItemService::create($restaurantId, $data, $_FILES)) {
            redirect('/owner/foods/create');
        }

        unset($_SESSION['_old']);
        flash('success', 'Food item added.');
        redirect('/owner/foods');
    }

    public function edit(string $id): void
    {
        $restaurantId = $this->tenantId();
        if ($restaurantId === null) {
            redirect('/owner');
        }

        $item = FoodItem::find((int) $id);
        if ($item === null || (int) $item['restaurant_id'] !== $restaurantId) {
            abort(404);
        }

        view('owner/foods/form', [
            'title'      => 'Edit food item',
            'heading'    => 'Edit food item — ' . $item['name'],
            'action'     => url('/owner/foods/' . (int) $item['id'] . '/edit'),
            'categories' => Category::allForRestaurant($restaurantId),
            'item'       => $item,
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

        $validator = new FoodItemValidator($data);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            redirect('/owner/foods/' . (int) $id . '/edit');
        }

        if (!FoodItemService::update($restaurantId, (int) $id, $data, $_FILES)) {
            redirect('/owner/foods');
        }

        unset($_SESSION['_old']);
        flash('success', 'Food item updated.');
        redirect('/owner/foods');
    }

    public function delete(string $id): void
    {
        $restaurantId = $this->tenantId();
        if ($restaurantId === null) {
            redirect('/owner');
        }

        if (!FoodItemService::delete($restaurantId, (int) $id)) {
            redirect('/owner/foods');
        }

        flash('success', 'Food item deleted.');
        redirect('/owner/foods');
    }

    /** Availability toggle from the item list. */
    public function toggleAvailable(string $id): void
    {
        $restaurantId = $this->tenantId();
        if ($restaurantId === null) {
            redirect('/owner');
        }

        if (!FoodItemService::toggleAvailable($restaurantId, (int) $id)) {
            redirect('/owner/foods');
        }

        flash('success', 'Food item availability updated.');
        redirect('/owner/foods');
    }

    private function formData(): array
    {
        return [
            'name'         => trim((string) ($_POST['name'] ?? '')),
            'category_id'  => (string) ($_POST['category_id'] ?? ''),
            'description'  => trim((string) ($_POST['description'] ?? '')),
            'price'        => trim((string) ($_POST['price'] ?? '')),
            'is_available' => (string) ($_POST['is_available'] ?? '1'),
        ];
    }
}
