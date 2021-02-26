<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Calendar;

use CultuurNet\UDB3\Model\Validation\ValueObject\EnumValidator;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;

class StatusTypeValidator extends EnumValidator
{
    protected function getAllowedValues(): array
    {
        return StatusType::getAllowedValues();
    }
}
