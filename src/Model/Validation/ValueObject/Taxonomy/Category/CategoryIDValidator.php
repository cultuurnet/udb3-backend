<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Taxonomy\Category;

use Respect\Validation\Rules\NotEmpty;
use Respect\Validation\Rules\StringType;
use Respect\Validation\Validator;

class CategoryIDValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new StringType(),
            new NotEmpty(),
        ];

        parent::__construct($rules);
    }
}
