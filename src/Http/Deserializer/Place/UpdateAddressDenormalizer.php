<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Place;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Language as LegacyLanguage;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Place\Commands\UpdateAddress;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class UpdateAddressDenormalizer implements DenormalizerInterface
{
    private string $placeId;

    private Language $language;

    public function __construct(string $placeId, Language $language)
    {
        $this->placeId = $placeId;
        $this->language = $language;
    }

    public function denormalize($data, $class, $format = null, array $context = []): UpdateAddress
    {
        return new UpdateAddress(
            $this->placeId,
            Address::deserialize($data),
            LegacyLanguage::fromUdb3ModelLanguage($this->language)
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === UpdateAddress::class;
    }
}
