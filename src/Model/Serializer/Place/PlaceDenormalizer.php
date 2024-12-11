<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\Place;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Model\Event\ImmutableEvent;
use CultuurNet\UDB3\Model\Place\ImmutablePlace;
use CultuurNet\UDB3\Model\Place\Place;
use CultuurNet\UDB3\Model\Place\PlaceIDParser;
use CultuurNet\UDB3\Model\Serializer\Offer\OfferDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Geography\CoordinatesDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Geography\TranslatedAddressDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject\VideoDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Geography\TranslatedAddress;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Identity\UuidParser;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class PlaceDenormalizer extends OfferDenormalizer
{
    private DenormalizerInterface $addressDenormalizer;

    private DenormalizerInterface $geoCoordinatesDenormalizer;

    public function __construct(
        UuidParser $placeIDParser = null,
        DenormalizerInterface $titleDenormalizer = null,
        DenormalizerInterface $descriptionDenormalizer = null,
        DenormalizerInterface $calendarDenormalizer = null,
        DenormalizerInterface $addressDenormalizer = null,
        DenormalizerInterface $categoriesDenormalizer = null,
        DenormalizerInterface $labelsDenormalizer = null,
        DenormalizerInterface $organizerReferenceDenormalizer = null,
        DenormalizerInterface $geoCoordinatesDenormalizer = null,
        DenormalizerInterface $ageRangeDenormalizer = null,
        DenormalizerInterface $priceInfoDenormalizer = null,
        DenormalizerInterface $bookingInfoDenormalizer = null,
        DenormalizerInterface $contactPointDenormalizer = null,
        DenormalizerInterface $mediaObjectReferencesDenormalizer = null,
        VideoDenormalizer $videoDenormalizer = null
    ) {
        if (!$placeIDParser) {
            $placeIDParser = new PlaceIDParser();
        }

        if (!$addressDenormalizer) {
            $addressDenormalizer = new TranslatedAddressDenormalizer();
        }

        if (!$geoCoordinatesDenormalizer) {
            $geoCoordinatesDenormalizer = new CoordinatesDenormalizer();
        }

        $this->addressDenormalizer = $addressDenormalizer;
        $this->geoCoordinatesDenormalizer = $geoCoordinatesDenormalizer;

        parent::__construct(
            $placeIDParser,
            $titleDenormalizer,
            $descriptionDenormalizer,
            $calendarDenormalizer,
            $categoriesDenormalizer,
            $labelsDenormalizer,
            $organizerReferenceDenormalizer,
            $ageRangeDenormalizer,
            $priceInfoDenormalizer,
            $bookingInfoDenormalizer,
            $contactPointDenormalizer,
            $mediaObjectReferencesDenormalizer,
            $videoDenormalizer
        );
    }

    protected function createOffer(
        array $originalData,
        Uuid $id,
        Language $mainLanguage,
        TranslatedTitle $title,
        Calendar $calendar,
        Categories $categories
    ): ImmutablePlace {
        if (!isset($originalData['address'])) {
            throw new UnsupportedException('Place data should contain an address.');
        }

        /* @var TranslatedAddress $address */
        $address = $this->addressDenormalizer->denormalize(
            $originalData['address'],
            TranslatedAddress::class,
            null,
            ['originalLanguage' => $originalData['mainLanguage']]
        );

        return new ImmutablePlace(
            $id,
            $mainLanguage,
            $title,
            $calendar,
            $address,
            $categories
        );
    }

    public function denormalize($data, $class, $format = null, array $context = []): ImmutablePlace
    {
        if (!$this->supportsDenormalization($data, $class, $format)) {
            throw new UnsupportedException("PlaceDenormalizer does not support {$class}.");
        }

        if (!is_array($data)) {
            throw new UnsupportedException('Place data should be an associative array.');
        }

        $offer = $this->denormalizeOffer($data);

        if (! $offer instanceof ImmutablePlace) {
            throw new UnsupportedException(sprintf('Expected an %s but got a %s', ImmutableEvent::class, get_class($offer)));
        }

        return $this->denormalizeGeoCoordinates($data, $offer);
    }

    private function denormalizeGeoCoordinates(array $data, ImmutablePlace $place): ImmutablePlace
    {
        if (isset($data['geo'])) {
            try {
                $coordinates = $this->geoCoordinatesDenormalizer->denormalize($data['geo'], Coordinates::class);
                $place = $place->withGeoCoordinates($coordinates);
            } catch (\Exception $e) {
                // Do nothing.
            }
        }

        return $place;
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === ImmutablePlace::class || $type === Place::class;
    }
}
