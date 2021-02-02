<?php

namespace CultuurNet\UDB3\Model\ValueObject\Geography;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Translation\TranslatedValueObject;

/**
 * @method Address getTranslation(Language $language)
 */
class TranslatedAddress extends TranslatedValueObject
{
    /**
     * @inheritdoc
     */
    protected function getValueObjectClassName()
    {
        return Address::class;
    }
}
