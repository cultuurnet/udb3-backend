<?php

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Taxonomy\Label;

use Respect\Validation\Rules\AlwaysValid;
use Respect\Validation\Rules\ArrayType;
use Respect\Validation\Rules\Each;
use Respect\Validation\Rules\When;
use Respect\Validation\Validator;

class LabelsValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new ArrayType(),
            new When(
                new ArrayType(),
                new Each(
                    (new LabelValidator())->setName('each label')
                ),
                new AlwaysValid()
            ),
        ];

        parent::__construct($rules);
    }
}
