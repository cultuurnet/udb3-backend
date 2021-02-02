<?php

namespace CultuurNet\UDB3\Model\ValueObject\Price;

use CultuurNet\UDB3\Model\ValueObject\Translation\TranslatedValueObject;

class TranslatedTariffName extends TranslatedValueObject
{
    /**
     * @inheritdoc
     */
    protected function getValueObjectClassName()
    {
        return TariffName::class;
    }
}
