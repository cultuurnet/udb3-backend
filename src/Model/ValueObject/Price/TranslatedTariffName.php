<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Price;

use CultuurNet\UDB3\Model\ValueObject\Translation\TranslatedValueObject;

class TranslatedTariffName extends TranslatedValueObject
{
    protected function getValueObjectClassName(): string
    {
        return TariffName::class;
    }
}
