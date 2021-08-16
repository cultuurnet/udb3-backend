<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Calendar;

use DateTimeInterface;
use Respect\Validation\Rules\AllOf;
use Respect\Validation\Rules\AlwaysValid;
use Respect\Validation\Rules\Date;
use Respect\Validation\Rules\Key;
use Respect\Validation\Rules\KeyValue;
use Respect\Validation\Rules\When;
use Respect\Validation\Validator;

class SubEventValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new Key('startDate', new Date(DateTimeInterface::ATOM), true),
            new Key('endDate', new Date(DateTimeInterface::ATOM), true),
            new When(
                new AllOf(
                    new Key('startDate', new Date(DateTimeInterface::ATOM)),
                    new Key('endDate', new Date(DateTimeInterface::ATOM))
                ),
                new KeyValue('endDate', 'min', 'startDate'),
                new AlwaysValid()
            ),
            new Key('status', new StatusValidator(), false),
        ];

        parent::__construct($rules);
    }
}
