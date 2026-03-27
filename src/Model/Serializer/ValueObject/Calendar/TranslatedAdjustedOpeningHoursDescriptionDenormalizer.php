<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Translation\TranslatedValueObjectDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\AdjustedOpeningHoursDescription;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedAdjustedOpeningHoursDescription;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

final class TranslatedAdjustedOpeningHoursDescriptionDenormalizer extends TranslatedValueObjectDenormalizer
{
    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === TranslatedAdjustedOpeningHoursDescription::class;
    }

    protected function createTranslatedValueObject(Language $originalLanguage, object $originalValue)
    {
        return new TranslatedAdjustedOpeningHoursDescription($originalLanguage, $originalValue);
    }

    protected function createValueObject($value)
    {
        return new AdjustedOpeningHoursDescription($value);
    }
}
