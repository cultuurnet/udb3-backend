<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Calendar\OpeningHours;

use Respect\Validation\Rules\AllOf;
use Respect\Validation\Rules\AlwaysValid;
use Respect\Validation\Rules\Date;
use Respect\Validation\Rules\Key;
use Respect\Validation\Rules\KeyValue;
use Respect\Validation\Rules\When;
use Respect\Validation\Validator;

class OpeningHourValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new Key('opens', new Date('H:i'), true),
            new Key('closes', new Date('H:i'), true),
            new When(
                new AllOf(
                    new Key('opens', new Date('H:i')),
                    new Key('closes', new Date('H:i'))
                ),
                new KeyValue('closes', 'min', 'opens'),
                new AlwaysValid()
            ),
            new Key('dayOfWeek', new DaysValidator(), true),
        ];

        parent::__construct($rules);
    }
}
