<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\Place;

use CultuurNet\UDB3\Model\Place\PlaceIDParser;
use CultuurNet\UDB3\Model\Place\PlaceReference;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Geography\TranslatedAddressDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Geography\TranslatedAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class PlaceReferenceDenormalizer implements DenormalizerInterface
{
    private PlaceIDParser $placeIDParser;
    private TranslatedAddressDenormalizer $addressDenormalizer;

    public function __construct(
        PlaceIDParser $placeIDParser,
        TranslatedAddressDenormalizer $addressDenormalizer = null
    ) {
        $this->placeIDParser = $placeIDParser;

        if (!$addressDenormalizer) {
            $this->addressDenormalizer = new TranslatedAddressDenormalizer();
        }
    }

    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (!$this->supportsDenormalization($data, $class, $format)) {
            throw new UnsupportedException("PlaceReferenceDenormalizer does not support {$class}.");
        }

        if (!is_array($data)) {
            throw new UnsupportedException('Location data should be an associative array.');
        }

        if (!isset($data['@id'])) {
            /** @var TranslatedAddress $translatedAddress */
            $translatedAddress = $this->addressDenormalizer->denormalize(
                $data['address'],
                TranslatedAddress::class,
                $format,
                $context
            );
            return PlaceReference::createWithAddress($translatedAddress);
        }

        $placeIdUrl = new Url($data['@id']);
        $placeId = $this->placeIDParser->fromUrl($placeIdUrl);

        return PlaceReference::createWithPlaceId($placeId);
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === PlaceReference::class;
    }
}
