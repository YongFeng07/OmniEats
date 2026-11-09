<?php

namespace App\Validators;

use App\Models\Voucher;

/**
 * Voucher form validation (Promotion & Voucher module — Student 1).
 * Duplicate codes are checked against the database (excluding the row
 * being edited); dates must form a valid window; discount values must
 * match their type.
 */
class VoucherValidator extends Validator
{
    public function __construct(array $data, ?int $exceptId = null)
    {
        parent::__construct($data);

        $this->required('code', 'title', 'discount_type', 'discount_value', 'starts_at', 'ends_at', 'usage_limit')
             ->maxLength('code', 30)
             ->maxLength('title', 100)
             ->inList('discount_type', ['percent', 'fixed'])
             ->nonNegativeNumber('discount_value')
             ->nonNegativeNumber('min_order')
             ->integerRange('usage_limit', 1, 100000);

        $code = trim((string) ($this->data['code'] ?? ''));
        if ($code !== '' && !preg_match('/^[A-Za-z0-9]+$/', $code)) {
            $this->errors['code'][] = 'The voucher code may only contain letters and numbers.';
        }

        if ($code !== '') {
            $existing = Voucher::findByCode($code);
            if ($existing !== null && (int) $existing['id'] !== $exceptId) {
                $this->errors['code'][] = 'This voucher code is already in use.';
            }
        }

        $type = (string) ($this->data['discount_type'] ?? '');
        $value = (float) ($this->data['discount_value'] ?? 0);
        if ($type === 'percent' && ($value <= 0 || $value > 100)) {
            $this->errors['discount_value'][] = 'A percentage discount must be between 1 and 100.';
        }
        if ($type === 'fixed' && $value <= 0) {
            $this->errors['discount_value'][] = 'A fixed discount must be greater than zero.';
        }

        $starts = trim((string) ($this->data['starts_at'] ?? ''));
        $ends = trim((string) ($this->data['ends_at'] ?? ''));
        if ($starts !== '' && strtotime($starts) === false) {
            $this->errors['starts_at'][] = 'Please enter a valid start date and time.';
        }
        if ($ends !== '' && strtotime($ends) === false) {
            $this->errors['ends_at'][] = 'Please enter a valid expiry date and time.';
        }
        if ($starts !== '' && $ends !== ''
            && strtotime($starts) !== false && strtotime($ends) !== false
            && strtotime($ends) <= strtotime($starts)) {
            $this->errors['ends_at'][] = 'The expiry date must be after the start date.';
        }

        // max_discount only applies to percent vouchers (optional cap).
        if ($this->data['max_discount'] !== '' && $this->data['max_discount'] !== null) {
            $this->nonNegativeNumber('max_discount');
        }
    }
}
