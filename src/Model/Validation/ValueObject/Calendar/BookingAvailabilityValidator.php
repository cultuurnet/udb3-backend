<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Calendar;

use Respect\Validation\Rules\ArrayType;
use Respect\Validation\Rules\Key;
use Respect\Validation\Validator;

class BookingAvailabilityValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new ArrayType(),
            new Key('type', new BookingAvailabilityTypeValidator(), false),
        ];

        parent::__construct($rules);
    }
}
