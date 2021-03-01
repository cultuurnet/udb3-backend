<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Calendar;

use CultuurNet\UDB3\Model\Validation\ValueObject\Calendar\OpeningHours\OpeningHoursValidator;
use Respect\Validation\Rules\AllOf;
use Respect\Validation\Rules\AlwaysValid;
use Respect\Validation\Rules\Date;
use Respect\Validation\Rules\Equals;
use Respect\Validation\Rules\Key;
use Respect\Validation\Rules\KeyValue;
use Respect\Validation\Rules\When;
use Respect\Validation\Validator;

class PeriodicCalendarValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new When(
                new Key('calendarType', new Equals('periodic'), true),
                (new AllOf(
                    new Key('startDate', new Date(\DateTime::ATOM), true),
                    new Key('endDate', new Date(\DateTime::ATOM), true),
                    new When(
                        new AllOf(
                            new Key('startDate', new Date(\DateTime::ATOM)),
                            new Key('endDate', new Date(\DateTime::ATOM))
                        ),
                        new KeyValue('endDate', 'min', 'startDate'),
                        new AlwaysValid()
                    ),
                    new Key('openingHours', new OpeningHoursValidator(), false)
                ))->setName('calendarType periodic'),
                new AlwaysValid()
            ),
        ];

        parent::__construct($rules);
    }
}
