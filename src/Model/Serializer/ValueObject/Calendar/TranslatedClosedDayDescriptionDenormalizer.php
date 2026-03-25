<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Translation\TranslatedValueObjectDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\ClosedDayDescription;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedClosedDayDescription;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

final class TranslatedClosedDayDescriptionDenormalizer extends TranslatedValueObjectDenormalizer
{
    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === TranslatedClosedDayDescription::class;
    }

    protected function createTranslatedValueObject(Language $originalLanguage, object $originalValue)
    {
        return new TranslatedClosedDayDescription($originalLanguage, $originalValue);
    }

    /**
     * @inheritdoc
     */
    protected function createValueObject($value)
    {
        return new ClosedDayDescription($value);
    }
}
