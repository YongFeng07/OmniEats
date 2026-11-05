<?php

namespace App\Validators;

use App\Models\Role;

/**
 * Admin "edit user" form. The password fields are optional here —
 * leaving them blank keeps the current password.
 */
class UserUpdateValidator extends Validator
{
    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->required('name', 'email', 'role', 'is_active')
             ->minLength('name', 2)
             ->maxLength('name', 100)
             ->email('email')
             ->phone('phone')
             ->inList('role', Role::NAMES)
             ->inList('is_active', ['0', '1'])
             ->requiredIf('restaurant_id', 'role', ['restaurant_owner', 'restaurant_staff']);

        if (($data['password'] ?? '') !== '') {
            $this->passwordStrength('password')
                 ->matches('password', 'password_confirmation');
        }
    }
}
