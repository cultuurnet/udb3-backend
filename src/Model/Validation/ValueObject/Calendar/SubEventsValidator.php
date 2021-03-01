<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Calendar;

use Respect\Validation\Rules\ArrayType;
use Respect\Validation\Rules\Each;
use Respect\Validation\Rules\Length;
use Respect\Validation\Validator;

class SubEventsValidator extends Validator
{
    public function __construct($minLength = 0)
    {
        $rules = [
            new ArrayType(),
            new Each(
                (new SubEventValidator())->setName('subEvent')
            ),
        ];

        if ($minLength > 0) {
            $rules[] = (new Length($minLength))
                ->setTemplate('{{name}} must have at least {{minValue}} subEvent(s)');
        }

        parent::__construct($rules);
    }
}
