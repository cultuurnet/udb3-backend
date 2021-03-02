<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\Organizer;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Model\Organizer\ImmutableOrganizer;
use CultuurNet\UDB3\Model\Organizer\Organizer;
use CultuurNet\UDB3\Model\Organizer\OrganizerIDParser;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Contact\ContactPointDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Geography\CoordinatesDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Geography\TranslatedAddressDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Taxonomy\Label\LabelsDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Text\TranslatedTitleDenormalizer;
use CultuurNet\UDB3\Model\Validation\Organizer\OrganizerValidator;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Geography\TranslatedAddress;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUIDParser;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use Respect\Validation\Validator;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class OrganizerDenormalizer implements DenormalizerInterface
{
    /**
     * @var Validator
     */
    private $organizerValidator;

    /**
     * @var UUIDParser
     */
    private $organizerIDParser;

    /**
     * @var DenormalizerInterface
     */
    private $titleDenormalizer;

    /**
     * @var DenormalizerInterface
     */
    private $addressDenormalizer;

    /**
     * @var DenormalizerInterface
     */
    private $labelsDenormalizer;

    /**
     * @var DenormalizerInterface
     */
    private $contactPointDenormalizer;

    /**
     * @var DenormalizerInterface
     */
    private $geoCoordinatesDenormalizer;

    public function __construct(
        Validator $organizerValidator = null,
        UUIDParser $organizerIDParser = null,
        DenormalizerInterface $titleDenormalizer = null,
        DenormalizerInterface $addressDenormalizer = null,
        DenormalizerInterface $labelsDenormalizer = null,
        DenormalizerInterface $contactPointDenormalizer = null,
        DenormalizerInterface $geoCoordinatesDenormalizer = null
    ) {
        if (!$organizerValidator) {
            $organizerValidator = new OrganizerValidator();
        }

        if (!$organizerIDParser) {
            $organizerIDParser = new OrganizerIDParser();
        }

        if (!$titleDenormalizer) {
            $titleDenormalizer = new TranslatedTitleDenormalizer();
        }

        if (!$addressDenormalizer) {
            $addressDenormalizer = new TranslatedAddressDenormalizer();
        }

        if (!$labelsDenormalizer) {
            $labelsDenormalizer = new LabelsDenormalizer();
        }

        if (!$contactPointDenormalizer) {
            $contactPointDenormalizer = new ContactPointDenormalizer();
        }

        if (!$geoCoordinatesDenormalizer) {
            $geoCoordinatesDenormalizer = new CoordinatesDenormalizer();
        }

        $this->organizerValidator = $organizerValidator;
        $this->organizerIDParser = $organizerIDParser;
        $this->titleDenormalizer = $titleDenormalizer;
        $this->addressDenormalizer = $addressDenormalizer;
        $this->labelsDenormalizer = $labelsDenormalizer;
        $this->contactPointDenormalizer = $contactPointDenormalizer;
        $this->geoCoordinatesDenormalizer = $geoCoordinatesDenormalizer;
    }

    /**
     * @inheritdoc
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (!$this->supportsDenormalization($data, $class, $format)) {
            throw new UnsupportedException("OrganizerDenormalizer does not support {$class}.");
        }

        if (!is_array($data)) {
            throw new UnsupportedException('Organizer data should be an associative array.');
        }

        $this->organizerValidator->assert($data);

        $idUrl = new Url($data['@id']);
        $id = $this->organizerIDParser->fromUrl($idUrl);

        $mainLanguageKey = $data['mainLanguage'];
        $mainLanguage = new Language($mainLanguageKey);

        /* @var TranslatedTitle $title */
        $title = $this->titleDenormalizer->denormalize(
            $data['name'],
            TranslatedTitle::class,
            null,
            ['originalLanguage' => $mainLanguageKey]
        );

        $url = null;
        if (isset($data['url'])) {
            $url = new Url($data['url']);
        }

        $organizer = new ImmutableOrganizer(
            $id,
            $mainLanguage,
            $title,
            $url
        );

        $organizer = $this->denormalizeAddress($data, $organizer);
        $organizer = $this->denormalizeLabels($data, $organizer);
        $organizer = $this->denormalizeContactPoint($data, $organizer);
        $organizer = $this->denormalizeGeoCoordinates($data, $organizer);

        return $organizer;
    }

    /**
     * @return ImmutableOrganizer
     */
    private function denormalizeAddress(array $data, ImmutableOrganizer $organizer)
    {
        if (isset($data['address'])) {
            /* @var TranslatedAddress $address */
            $address = $this->addressDenormalizer->denormalize($data['address'], TranslatedAddress::class);
            $organizer = $organizer->withAddress($address);
        }

        return $organizer;
    }

    /**
     * @return ImmutableOrganizer
     */
    private function denormalizeLabels(array $data, ImmutableOrganizer $organizer)
    {
        $labels = $this->labelsDenormalizer->denormalize($data, Labels::class);
        return $organizer->withLabels($labels);
    }

    /**
     * @return ImmutableOrganizer
     */
    private function denormalizeGeoCoordinates(array $data, ImmutableOrganizer $organizer)
    {
        if (isset($data['geo'])) {
            try {
                $coordinates = $this->geoCoordinatesDenormalizer->denormalize($data['geo'], Coordinates::class);
                $organizer = $organizer->withGeoCoordinates($coordinates);
            } catch (\Exception $e) {
                // Do nothing.
            }
        }

        return $organizer;
    }

    /**
     * @return ImmutableOrganizer
     */
    protected function denormalizeContactPoint(array $data, ImmutableOrganizer $organizer)
    {
        if (isset($data['contactPoint'])) {
            $contactPoint = $this->contactPointDenormalizer->denormalize(
                $data['contactPoint'],
                ContactPoint::class,
                null,
                ['originalLanguage' => $data['mainLanguage']]
            );
            $organizer = $organizer->withContactPoint($contactPoint);
        }

        return $organizer;
    }

    /**
     * @inheritdoc
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === ImmutableOrganizer::class || $type === Organizer::class;
    }
}
