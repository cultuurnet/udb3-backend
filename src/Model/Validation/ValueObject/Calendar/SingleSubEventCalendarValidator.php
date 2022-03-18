<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Calendar;

use DateTimeInterface;
use Respect\Validation\Rules\AllOf;
use Respect\Validation\Rules\AlwaysValid;
use Respect\Validation\Rules\Date;
use Respect\Validation\Rules\Each;
use Respect\Validation\Rules\Equals;
use Respect\Validation\Rules\Key;
use Respect\Validation\Rules\KeyValue;
use Respect\Validation\Rules\Length;
use Respect\Validation\Rules\When;
use Respect\Validation\Validator;

class SingleSubEventCalendarValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new When(
                new Key('calendarType', new Equals('single'), true),
                (new AllOf(
                    new Key('startDate', new Date(DateTimeInterface::ATOM), false),
                    new Key('endDate', new Date(DateTimeInterface::ATOM), false),
                    new When(
                        new AllOf(
                            new Key('startDate', new Date(DateTimeInterface::ATOM)),
                            new Key('endDate', new Date(DateTimeInterface::ATOM))
                        ),
                        new KeyValue('endDate', 'min', 'startDate'),
                        new AlwaysValid()
                    ),
                    new Key(
                        'subEvent',
                        new AllOf(
                            (new Length(0, 1))
                                ->setName('calendarType single')
                                ->setTemplate('{{name}} should have exactly one subEvent'),
                            new Each(
                                (new SubEventValidator())->setName('subEvent')
                            )
                        ),
                        false
                    )
                ))->setName('calendarType single'),
                new AlwaysValid()
            ),
        ];

        parent::__construct($rules);
    }
}
