<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Translation\TranslatedValueObject;

class TranslatedStatusReason extends TranslatedValueObject
{
    protected function getValueObjectClassName(): string
    {
        return StatusReason::class;
    }
}
