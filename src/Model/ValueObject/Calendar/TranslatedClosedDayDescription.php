<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Translation\TranslatedValueObject;

final class TranslatedClosedDayDescription extends TranslatedValueObject
{
    protected function getValueObjectClassName(): string
    {
        // Both ClosedDay and AdjustedOpeningHours descriptions share the same constraints
        // (non-empty string, max 1000 characters), so they reuse AdjustedDescription as the
        // leaf value object. If the constraints ever diverge, split into separate classes.
        return AdjustedDescription::class;
    }
}
