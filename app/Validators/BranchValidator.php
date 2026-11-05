<?php

namespace App\Validators;

/**
 * Branch form (Module 2). Address and opening hours are optional —
 * a branch may inherit the restaurant's details.
 */
class BranchValidator extends Validator
{
    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->required('name')
             ->minLength('name', 2)
             ->maxLength('name', 100)
             ->maxLength('address', 255)
             ->maxLength('opening_hours', 255)
             ->phone('phone');
    }
}
