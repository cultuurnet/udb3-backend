<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Taxonomy\Category;

use Respect\Validation\Rules\Key;
use Respect\Validation\Validator;

class CategoryValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            (new Key('id', new CategoryIDValidator(), true))
                ->setName('term id'),
        ];

        parent::__construct($rules);
    }
}
