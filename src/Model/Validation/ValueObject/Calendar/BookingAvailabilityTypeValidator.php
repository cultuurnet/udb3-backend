<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Calendar;

use CultuurNet\UDB3\Model\Validation\ValueObject\EnumValidator;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;

class BookingAvailabilityTypeValidator extends EnumValidator
{
    protected function getAllowedValues(): array
    {
        return BookingAvailabilityType::getAllowedValues();
    }
}
