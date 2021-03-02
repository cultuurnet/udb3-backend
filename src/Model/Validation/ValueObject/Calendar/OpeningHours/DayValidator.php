<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Calendar\OpeningHours;

use CultuurNet\UDB3\Model\Validation\ValueObject\EnumValidator;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Day;

class DayValidator extends EnumValidator
{
    /**
     * @inheritdoc
     */
    protected function getAllowedValues()
    {
        return Day::getAllowedValues();
    }
}
