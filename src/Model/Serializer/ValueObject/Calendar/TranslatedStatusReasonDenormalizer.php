<?php

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Translation\TranslatedValueObjectDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusReason;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedStatusReason;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

class TranslatedStatusReasonDenormalizer extends TranslatedValueObjectDenormalizer
{
    /**
     * @inheritdoc
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === TranslatedStatusReason::class;
    }

    /**
     * @inheritdoc
     */
    protected function createTranslatedValueObject(Language $originalLanguage, $originalValue)
    {
        return new TranslatedStatusReason($originalLanguage, $originalValue);
    }

    /**
     * @inheritdoc
     */
    protected function createValueObject($value)
    {
        return new StatusReason($value);
    }
}
