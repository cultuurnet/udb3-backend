<?php

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Calendar\OpeningHours;

use Respect\Validation\Rules\ArrayType;
use Respect\Validation\Rules\Each;
use Respect\Validation\Validator;

class OpeningHoursValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new ArrayType(),
            new Each(
                (new OpeningHourValidator())->setName('openingHour')
            ),
        ];

        parent::__construct($rules);
    }
}
