<?php

namespace App\Validators;

/**
 * Own-profile update (name, email, phone) for every logged-in user.
 */
class ProfileValidator extends Validator
{
    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->required('name', 'email')
             ->minLength('name', 2)
             ->maxLength('name', 100)
             ->email('email')
             ->phone('phone');
    }
}
