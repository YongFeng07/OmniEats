<?php

namespace App\Validators;

/**
 * Food category form (Module 3). Names are unique per restaurant —
 * the duplicate check runs in CategoryService.
 */
class CategoryValidator extends Validator
{
    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->required('name')
             ->minLength('name', 2)
             ->maxLength('name', 100)
             ->maxLength('description', 255);

        // sort_order: optional, integer 0-999 when provided
        if (($data['sort_order'] ?? '') !== '') {
            $this->inList('sort_order', array_map('strval', range(0, 999)));
        }
    }
}
