<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Geography;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Translation\TranslatedValueObject;

/**
 * @method Address getTranslation(Language $language)
 */
class TranslatedAddress extends TranslatedValueObject
{
    protected function getValueObjectClassName(): string
    {
        return Address::class;
    }
}
