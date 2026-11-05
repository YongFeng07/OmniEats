<?php

namespace App\Validators;

/**
 * Change-password form (current + new + confirmation).
 */
class PasswordValidator extends Validator
{
    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->required('current_password', 'new_password', 'new_password_confirmation')
             ->passwordStrength('new_password')
             ->matches('new_password', 'new_password_confirmation');
    }
}
