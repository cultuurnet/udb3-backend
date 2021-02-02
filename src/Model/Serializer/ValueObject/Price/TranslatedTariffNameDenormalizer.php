<?php

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Price;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Translation\TranslatedValueObjectDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Price\TariffName;
use CultuurNet\UDB3\Model\ValueObject\Price\TranslatedTariffName;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

class TranslatedTariffNameDenormalizer extends TranslatedValueObjectDenormalizer
{
    /**
     * @inheritdoc
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === TranslatedTariffName::class;
    }

    /**
     * @inheritdoc
     */
    protected function createTranslatedValueObject(Language $originalLanguage, $originalValue)
    {
        return new TranslatedTariffName($originalLanguage, $originalValue);
    }

    /**
     * @inheritdoc
     */
    protected function createValueObject($value)
    {
        return new TariffName($value);
    }
}
