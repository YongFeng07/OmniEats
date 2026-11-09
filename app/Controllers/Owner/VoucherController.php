<?php

namespace App\Controllers\Owner;

use App\Models\Voucher;
use App\Services\AuthService;
use App\Validators\VoucherValidator;

/**
 * Voucher management — restaurant owner (Promotion & Voucher module,
 * Student 1). Owners manage vouchers for THEIR OWN restaurant only;
 * the tenant id always comes from the session, never from the form.
 */
class VoucherController
{
    private function tenantId(): ?int
    {
        $restaurantId = AuthService::user()['restaurant_id'] ?? null;
        return $restaurantId !== null ? (int) $restaurantId : null;
    }

    /** The voucher if it belongs to this tenant, else 404. */
    private function ownVoucher(int $id): array
    {
        $voucher = Voucher::find($id);
        $tenantId = $this->tenantId();
        if ($voucher === null || $voucher['restaurant_id'] === null
            || (int) $voucher['restaurant_id'] !== $tenantId) {
            abort(404);
        }
        return $voucher;
    }

    public function index(): void
    {
        $restaurantId = $this->tenantId();
        if ($restaurantId === null) {
            redirect('/owner');
        }

        view('owner/vouchers/index', [
            'title'    => 'My Vouchers',
            'vouchers' => Voucher::listingForRestaurant($restaurantId),
        ]);
    }

    public function create(): void
    {
        view('owner/vouchers/form', [
            'title'   => 'Create voucher',
            'action'  => url('/owner/vouchers/create'),
            'voucher' => null,
        ]);
    }

    public function store(): void
    {
        $restaurantId = $this->tenantId();
        if ($restaurantId === null) {
            redirect('/owner');
        }

        $data = $this->formData();
        $data['restaurant_id'] = (string) $restaurantId; // tenant from session
        $_SESSION['_old'] = $data;

        $validator = new VoucherValidator($data);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            redirect('/owner/vouchers/create');
        }

        Voucher::create($data);
        unset($_SESSION['_old']);

        activity('create_voucher', 'voucher', Voucher::findByCode($data['code'])['id'], $data['code']);
        flash('success', 'Voucher "' . $data['code'] . '" created for your restaurant.');
        redirect('/owner/vouchers');
    }

    public function edit(string $id): void
    {
        $voucher = $this->ownVoucher((int) $id);

        view('owner/vouchers/form', [
            'title'   => 'Edit voucher',
            'action'  => url('/owner/vouchers/' . (int) $voucher['id'] . '/edit'),
            'voucher' => $voucher,
        ]);
    }

    public function update(string $id): void
    {
        $voucher = $this->ownVoucher((int) $id);

        $data = $this->formData();
        $data['restaurant_id'] = (string) $voucher['restaurant_id'];
        $_SESSION['_old'] = $data;

        $validator = new VoucherValidator($data, (int) $id);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            redirect('/owner/vouchers/' . (int) $id . '/edit');
        }

        Voucher::update((int) $id, $data);
        unset($_SESSION['_old']);

        activity('update_voucher', 'voucher', (int) $id, $data['code']);
        flash('success', 'Voucher updated.');
        redirect('/owner/vouchers');
    }

    /** Soft delete/restore — own vouchers only. */
    public function toggle(string $id): void
    {
        $voucher = $this->ownVoucher((int) $id);

        $active = (int) $voucher['is_active'] !== 1;
        Voucher::setActive((int) $id, $active);
        activity($active ? 'activate_voucher' : 'archive_voucher', 'voucher', (int) $id);

        flash('success', $active ? 'Voucher activated.' : 'Voucher archived.');
        redirect('/owner/vouchers');
    }

    private function formData(): array
    {
        return [
            'code'          => strtoupper(trim((string) ($_POST['code'] ?? ''))),
            'title'         => trim((string) ($_POST['title'] ?? '')),
            'discount_type' => (string) ($_POST['discount_type'] ?? ''),
            'discount_value'=> trim((string) ($_POST['discount_value'] ?? '')),
            'min_order'     => trim((string) ($_POST['min_order'] ?? '0')),
            'max_discount'  => trim((string) ($_POST['max_discount'] ?? '')),
            'starts_at'     => trim((string) ($_POST['starts_at'] ?? '')),
            'ends_at'       => trim((string) ($_POST['ends_at'] ?? '')),
            'usage_limit'   => trim((string) ($_POST['usage_limit'] ?? '100')),
            'is_active'     => isset($_POST['is_active']) && $_POST['is_active'] === '1' ? '1' : '0',
        ];
    }
}
