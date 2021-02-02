<?php

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Taxonomy\Category;

use Respect\Validation\Rules\ArrayType;
use Respect\Validation\Rules\Each;
use Respect\Validation\Rules\Length;
use Respect\Validation\Validator;

class CategoriesValidator extends Validator
{
    public function __construct($minLength = 0)
    {
        $rules = [
            new ArrayType(),
            new Each(
                (new CategoryValidator())->setName('term')
            ),
        ];

        if ($minLength > 0) {
            $rules[] = (new Length($minLength))
                ->setTemplate('{{name}} must have at least {{minValue}} term(s)');
        }

        parent::__construct($rules);
    }
}
