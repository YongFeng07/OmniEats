<?php

namespace App\Validators;

/**
 * Public registration (customer and restaurant owner sign-up).
 */
class RegisterValidator extends Validator
{
    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->required('name', 'email', 'password', 'password_confirmation')
             ->minLength('name', 2)
             ->maxLength('name', 100)
             ->email('email')
             ->phone('phone')
             ->passwordStrength('password')
             ->matches('password', 'password_confirmation');
    }
}
