<?php

namespace CultuurNet\UDB3\Model\Serializer\Place;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Model\Place\ImmutablePlace;
use CultuurNet\UDB3\Model\Place\Place;
use CultuurNet\UDB3\Model\Place\PlaceIDParser;
use CultuurNet\UDB3\Model\Serializer\Offer\OfferDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Geography\CoordinatesDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Geography\TranslatedAddressDenormalizer;
use CultuurNet\UDB3\Model\Validation\Place\PlaceValidator;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Geography\TranslatedAddress;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUIDParser;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use Respect\Validation\Validator;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class PlaceDenormalizer extends OfferDenormalizer
{
    /**
     * @var Validator
     */
    private $placeValidator;

    /**
     * @var DenormalizerInterface
     */
    private $addressDenormalizer;

    /**
     * @var DenormalizerInterface
     */
    private $geoCoordinatesDenormalizer;

    public function __construct(
        Validator $placeValidator = null,
        UUIDParser $placeIDParser = null,
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
        DenormalizerInterface $mediaObjectReferencesDenormalizer = null
    ) {
        if (!$placeValidator) {
            $placeValidator = new PlaceValidator();
        }

        if (!$placeIDParser) {
            $placeIDParser = new PlaceIDParser();
        }

        if (!$addressDenormalizer) {
            $addressDenormalizer = new TranslatedAddressDenormalizer();
        }

        if (!$geoCoordinatesDenormalizer) {
            $geoCoordinatesDenormalizer = new CoordinatesDenormalizer();
        }

        $this->placeValidator = $placeValidator;
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
            $mediaObjectReferencesDenormalizer
        );
    }

    /**
     * @inheritdoc
     */
    protected function createOffer(
        array $originalData,
        UUID $id,
        Language $mainLanguage,
        TranslatedTitle $title,
        Calendar $calendar,
        Categories $categories
    ) {
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

    /**
     * @inheritdoc
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (!$this->supportsDenormalization($data, $class, $format)) {
            throw new UnsupportedException("PlaceDenormalizer does not support {$class}.");
        }

        if (!is_array($data)) {
            throw new UnsupportedException('Place data should be an associative array.');
        }

        $this->placeValidator->assert($data);

        /* @var ImmutablePlace $offer */
        $offer = $this->denormalizeOffer($data);
        $offer = $this->denormalizeGeoCoordinates($data, $offer);

        return $offer;
    }

    /**
     * @param array $data
     * @param ImmutablePlace $place
     * @return ImmutablePlace
     */
    private function denormalizeGeoCoordinates(array $data, ImmutablePlace $place)
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

    /**
     * @inheritdoc
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === ImmutablePlace::class || $type === Place::class;
    }
}
