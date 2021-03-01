<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Calendar\OpeningHours;

use Respect\Validation\Rules\ArrayType;
use Respect\Validation\Rules\Each;
use Respect\Validation\Validator;

class DaysValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new ArrayType(),
            new Each(
                (new DayValidator())->setName('dayOfWeek')
            ),
        ];

        parent::__construct($rules);
    }
}
