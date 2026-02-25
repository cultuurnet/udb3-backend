<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Faq;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Translation\TranslatedValueObject;

/**
 * @method FaqItem getTranslation(Language $language)
 */
final class TranslatedFaqItem extends TranslatedValueObject
{
    protected function getValueObjectClassName(): string
    {
        return FaqItem::class;
    }
}
