<?php

namespace App\Validators;

/**
 * Food item form (Module 3). The category must exist and belong to the
 * tenant — checked in FoodItemService, never trusted from the browser.
 */
class FoodItemValidator extends Validator
{
    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->required('name', 'category_id', 'price')
             ->minLength('name', 2)
             ->maxLength('name', 100)
             ->maxLength('description', 1000)
             ->positiveNumber('price')
             ->inList('is_available', ['0', '1']);
    }
}
