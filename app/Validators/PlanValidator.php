<?php

namespace App\Validators;

/**
 * Subscription plan form (Module 4, admin).
 * max_food_items may be empty — that means "unlimited".
 */
class PlanValidator extends Validator
{
    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->required('name', 'price', 'max_branches')
             ->minLength('name', 2)
             ->maxLength('name', 50)
             ->nonNegativeNumber('price')
             ->positiveNumber('max_branches')
             ->maxLength('description', 1000)
             ->maxLength('features', 1000)
             ->inList('is_active', ['0', '1']);

        // Food item limit: optional, but when given it must be >= 1.
        if (($data['max_food_items'] ?? '') !== '') {
            $this->positiveNumber('max_food_items');
        }
    }
}
