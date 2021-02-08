<?php

namespace CultuurNet\UDB3\Model\Serializer\Place;

use CultuurNet\UDB3\Model\Place\Place;
use CultuurNet\UDB3\Model\Place\PlaceIDParser;
use CultuurNet\UDB3\Model\Place\PlaceReference;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class PlaceReferenceDenormalizer implements DenormalizerInterface
{
    /**
     * @var PlaceIDParser
     */
    private $placeIDParser;

    /**
     * @var PlaceDenormalizer
     */
    private $placeDenormalizer;

    public function __construct(
        PlaceIDParser $placeIDParser,
        PlaceDenormalizer $placeDenormalizer
    ) {
        $this->placeIDParser = $placeIDParser;
        $this->placeDenormalizer = $placeDenormalizer;
    }

    public function denormalize($data, $class, $format = null, array $context = array())
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
        $place = null;
        if (count($data) > 1) {
            try {
                $place = $this->placeDenormalizer->denormalize($data, Place::class);
            } catch (\Exception $e) {
                $place = null;
            }
        }

        if ($place) {
            return PlaceReference::createWithEmbeddedPlace($place);
        } else {
            return PlaceReference::createWithPlaceId($placeId);
        }
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === PlaceReference::class;
    }
}
