<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Text;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Translation\TranslatedValueObject;

/**
 * @method Title getTranslation(Language $language)
 */
class TranslatedTitle extends TranslatedValueObject
{
    protected function getValueObjectClassName()
    {
        return Title::class;
    }
}
