<?php

namespace App\Validators;

/**
 * Restaurant registration / profile form (Module 2).
 * Image uploads are validated separately in UploadService.
 */
class RestaurantValidator extends Validator
{
    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->required('name', 'phone', 'address', 'city', 'state', 'postcode', 'opening_hours')
             ->minLength('name', 2)
             ->maxLength('name', 100)
             ->maxLength('description', 1000)
             ->maxLength('opening_hours', 255)
             ->phone('phone')
             ->email('email');
    }
}
