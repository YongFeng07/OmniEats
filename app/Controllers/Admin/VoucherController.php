<?php

namespace App\Controllers\Admin;

use App\Models\Restaurant;
use App\Models\Voucher;
use App\Validators\VoucherValidator;

/**
 * Voucher management — Super Admin (Promotion & Voucher module,
 * Student 1). Full CRUD over every voucher on the platform: global
 * vouchers (restaurant_id NULL) or vouchers tied to one tenant.
 * Delete = soft archive (is_active) so redemption history survives.
 */
class VoucherController
{
    public function index(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $active = in_array($_GET['active'] ?? '', ['active', 'inactive'], true) ? $_GET['active'] : '';

        view('admin/vouchers/index', [
            'title'    => 'Vouchers',
            'vouchers' => Voucher::listing($q !== '' ? $q : null, $active !== '' ? $active : null),
            'q'        => $q,
            'fActive'  => $active,
        ]);
    }

    public function create(): void
    {
        view('admin/vouchers/form', [
            'title'       => 'Create voucher',
            'action'      => url('/admin/vouchers/create'),
            'voucher'     => null,
            'restaurants' => Restaurant::all(),
        ]);
    }

    public function store(): void
    {
        $data = $this->formData();
        $_SESSION['_old'] = $data;

        $validator = new VoucherValidator($data);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            redirect('/admin/vouchers/create');
        }

        Voucher::create($data);
        unset($_SESSION['_old']);

        activity('create_voucher', 'voucher', Voucher::findByCode($data['code'])['id'], $data['code']);
        flash('success', 'Voucher "' . $data['code'] . '" created.');
        redirect('/admin/vouchers');
    }

    public function edit(string $id): void
    {
        $voucher = Voucher::find((int) $id);
        if ($voucher === null) {
            abort(404);
        }

        view('admin/vouchers/form', [
            'title'       => 'Edit voucher',
            'action'      => url('/admin/vouchers/' . (int) $id . '/edit'),
            'voucher'     => $voucher,
            'restaurants' => Restaurant::all(),
        ]);
    }

    public function update(string $id): void
    {
        $voucher = Voucher::find((int) $id);
        if ($voucher === null) {
            abort(404);
        }

        $data = $this->formData();
        $_SESSION['_old'] = $data;

        $validator = new VoucherValidator($data, (int) $id);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            redirect('/admin/vouchers/' . (int) $id . '/edit');
        }

        Voucher::update((int) $id, $data);
        unset($_SESSION['_old']);

        activity('update_voucher', 'voucher', (int) $id, $data['code']);
        flash('success', 'Voucher updated.');
        redirect('/admin/vouchers');
    }

    /** Soft delete/restore (voucher DELETE — records preserved). */
    public function toggle(string $id): void
    {
        $voucher = Voucher::find((int) $id);
        if ($voucher === null) {
            abort(404);
        }

        $active = (int) $voucher['is_active'] !== 1;
        Voucher::setActive((int) $id, $active);
        activity($active ? 'activate_voucher' : 'archive_voucher', 'voucher', (int) $id);

        flash('success', $active ? 'Voucher activated.' : 'Voucher archived — customers can no longer use it.');
        redirect('/admin/vouchers');
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
            'restaurant_id' => (string) ($_POST['restaurant_id'] ?? ''),
            'is_active'     => isset($_POST['is_active']) && $_POST['is_active'] === '1' ? '1' : '0',
        ];
    }
}
