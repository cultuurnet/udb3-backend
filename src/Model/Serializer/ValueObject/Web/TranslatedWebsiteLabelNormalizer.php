<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Web;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Translation\TranslatedValueObjectNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Web\TranslatedWebsiteLabel;

final class TranslatedWebsiteLabelNormalizer extends TranslatedValueObjectNormalizer
{
    public function supportsNormalization($data, $format = null): bool
    {
        return $data === TranslatedWebsiteLabel::class;
    }
}
