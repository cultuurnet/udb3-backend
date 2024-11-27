<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Price;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Translation\TranslatedValueObjectNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Price\TranslatedTariffName;

final class TranslatedTariffNameNormalizer extends TranslatedValueObjectNormalizer
{
    public function supportsNormalization($data, $format = null): bool
    {
        return $data === TranslatedTariffName::class;
    }
}
