<?php

namespace App\Validators;

class LoginValidator extends Validator
{
    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->required('email', 'password')
             ->email('email');
    }
}
