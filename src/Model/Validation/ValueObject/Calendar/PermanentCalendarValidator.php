<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Calendar;

use CultuurNet\UDB3\Model\Validation\ValueObject\Calendar\OpeningHours\OpeningHoursValidator;
use Respect\Validation\Rules\AlwaysValid;
use Respect\Validation\Rules\Equals;
use Respect\Validation\Rules\Key;
use Respect\Validation\Rules\When;
use Respect\Validation\Validator;

class PermanentCalendarValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new When(
                new Key('calendarType', new Equals('permanent'), true),
                new Key('openingHours', new OpeningHoursValidator(), false),
                new AlwaysValid()
            ),
        ];

        parent::__construct($rules);
    }
}
