<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\Place;

use CultuurNet\UDB3\Model\Place\PlaceIDParser;
use CultuurNet\UDB3\Model\Place\PlaceReference;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class PlaceReferenceDenormalizer implements DenormalizerInterface
{
    private PlaceIDParser $placeIDParser;

    public function __construct(PlaceIDParser $placeIDParser)
    {
        $this->placeIDParser = $placeIDParser;
    }

    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (!$this->supportsDenormalization($data, $class, $format)) {
            throw new UnsupportedException("PlaceReferenceDenormalizer does not support {$class}.");
        }

        if (!is_array($data)) {
            throw new UnsupportedException('Location data should be an associative array.');
        }

        // @todo Support dummy locations.
        $placeIdUrl = new Url($data['@id']);
        $placeId = $this->placeIDParser->fromUrl($placeIdUrl);

        return PlaceReference::createWithPlaceId($placeId);
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === PlaceReference::class;
    }
}
