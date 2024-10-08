<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Web;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Translation\TranslatedValueObjectDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\TranslatedWebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLabel;

class TranslatedWebsiteLabelDenormalizer extends TranslatedValueObjectDenormalizer
{
    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === TranslatedWebsiteLabel::class;
    }

    /**
     * @inheritdoc
     */
    protected function createTranslatedValueObject(Language $originalLanguage, object $originalValue)
    {
        return new TranslatedWebsiteLabel($originalLanguage, $originalValue);
    }

    /**
     * @inheritdoc
     */
    protected function createValueObject($value)
    {
        return new WebsiteLabel($value);
    }
}
