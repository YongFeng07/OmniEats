<?php

namespace App\Validators;

/**
 * Small reusable server-side validation base.
 *
 * Each entity gets its own subclass (see LoginValidator) that applies
 * the relevant rules in its constructor. Frontend `required` attributes
 * are only a convenience — every rule here runs on the server.
 */
abstract class Validator
{
    protected array $errors = [];

    public function __construct(protected array $data)
    {
    }

    public function required(string ...$fields): static
    {
        foreach ($fields as $field) {
            $value = trim((string) ($this->data[$field] ?? ''));
            if ($value === '') {
                $this->errors[$field][] = 'This field is required.';
            }
        }
        return $this;
    }

    public function email(string $field): static
    {
        $value = (string) ($this->data[$field] ?? '');
        if ($value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $this->errors[$field][] = 'Please enter a valid email address.';
        }
        return $this;
    }

    public function minLength(string $field, int $length): static
    {
        $value = (string) ($this->data[$field] ?? '');
        if ($value !== '' && mb_strlen($value) < $length) {
            $this->errors[$field][] = "Must be at least {$length} characters.";
        }
        return $this;
    }

    public function maxLength(string $field, int $length): static
    {
        $value = (string) ($this->data[$field] ?? '');
        if (mb_strlen($value) > $length) {
            $this->errors[$field][] = "Must not exceed {$length} characters.";
        }
        return $this;
    }

    /** Confirm field (e.g. password confirmation) must match the field. */
    public function matches(string $field, string $confirmField): static
    {
        if (($this->data[$field] ?? '') !== ($this->data[$confirmField] ?? '')) {
            $this->errors[$confirmField][] = 'Values do not match.';
        }
        return $this;
    }

    /** Numeric value greater than zero — used for prices and quantities. */
    public function positiveNumber(string $field): static
    {
        $value = (string) ($this->data[$field] ?? '');
        if ($value !== '' && (!is_numeric($value) || (float) $value <= 0)) {
            $this->errors[$field][] = 'Must be a number greater than zero.';
        }
        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    /** First error message for a field, or null. */
    public function first(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }
}
