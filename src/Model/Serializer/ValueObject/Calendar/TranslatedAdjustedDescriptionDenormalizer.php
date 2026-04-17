<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Translation\TranslatedValueObjectDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\AdjustedDescription;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedAdjustedDescription;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

final class TranslatedAdjustedDescriptionDenormalizer extends TranslatedValueObjectDenormalizer
{
    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === TranslatedAdjustedDescription::class;
    }

    protected function createTranslatedValueObject(Language $originalLanguage, object $originalValue)
    {
        return new TranslatedAdjustedDescription($originalLanguage, $originalValue);
    }

    protected function createValueObject($value)
    {
        return new AdjustedDescription($value);
    }
}
