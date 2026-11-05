<?php

namespace App\Validators;

use App\Models\Role;

/**
 * Admin "create user" form. Role names come from a whitelist — a
 * submitted role value that is not one of the four platform roles is
 * rejected (prevents role escalation through form tampering).
 */
class UserCreateValidator extends Validator
{
    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->required('name', 'email', 'role', 'password', 'password_confirmation')
             ->minLength('name', 2)
             ->maxLength('name', 100)
             ->email('email')
             ->phone('phone')
             ->inList('role', Role::NAMES)
             ->requiredIf('restaurant_id', 'role', ['restaurant_owner', 'restaurant_staff'])
             ->passwordStrength('password')
             ->matches('password', 'password_confirmation');
    }
}
