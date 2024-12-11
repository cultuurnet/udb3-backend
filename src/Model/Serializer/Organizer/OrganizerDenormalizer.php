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
use CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject\ImagesDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Taxonomy\Label\LabelsDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Text\TranslatedDescriptionDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Text\TranslatedTitleDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Geography\TranslatedAddress;
use CultuurNet\UDB3\Model\ValueObject\Identity\UuidParser;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Images;
use CultuurNet\UDB3\Model\ValueObject\Moderation\Organizer\WorkflowStatus;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedDescription;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class OrganizerDenormalizer implements DenormalizerInterface
{
    private UuidParser $organizerIDParser;

    private DenormalizerInterface $titleDenormalizer;

    private DenormalizerInterface $descriptionDenormalizer;

    private DenormalizerInterface $addressDenormalizer;

    private DenormalizerInterface $labelsDenormalizer;

    private DenormalizerInterface $contactPointDenormalizer;

    private DenormalizerInterface $geoCoordinatesDenormalizer;

    private DenormalizerInterface $imagesDenormalizer;

    public function __construct(
        UuidParser $organizerIDParser = null,
        DenormalizerInterface $titleDenormalizer = null,
        DenormalizerInterface $descriptionDenormalizer = null,
        DenormalizerInterface $addressDenormalizer = null,
        DenormalizerInterface $labelsDenormalizer = null,
        DenormalizerInterface $contactPointDenormalizer = null,
        DenormalizerInterface $geoCoordinatesDenormalizer = null,
        DenormalizerInterface $imagesDenormalizer = null
    ) {
        if (!$organizerIDParser) {
            $organizerIDParser = new OrganizerIDParser();
        }

        if (!$titleDenormalizer) {
            $titleDenormalizer = new TranslatedTitleDenormalizer();
        }

        if (!$descriptionDenormalizer) {
            $descriptionDenormalizer = new TranslatedDescriptionDenormalizer();
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

        if (!$imagesDenormalizer) {
            $imagesDenormalizer = new ImagesDenormalizer();
        }

        $this->organizerIDParser = $organizerIDParser;
        $this->titleDenormalizer = $titleDenormalizer;
        $this->descriptionDenormalizer = $descriptionDenormalizer;
        $this->addressDenormalizer = $addressDenormalizer;
        $this->labelsDenormalizer = $labelsDenormalizer;
        $this->contactPointDenormalizer = $contactPointDenormalizer;
        $this->geoCoordinatesDenormalizer = $geoCoordinatesDenormalizer;
        $this->imagesDenormalizer = $imagesDenormalizer;
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

        if (!isset($data['@id'])) {
            throw new UnsupportedException('Organizer data should contain an @id property.');
        }

        $idUrl = new Url($data['@id']);
        $id = $this->organizerIDParser->fromUrl($idUrl);

        $mainLanguageKey = $data['mainLanguage'] ?? 'nl';
        $data['mainLanguage'] = $mainLanguageKey;
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

        $organizer = $this->denormalizeDescription($data, $organizer);
        $organizer = $this->denormalizeEducationalDescription($data, $organizer);
        $organizer = $this->denormalizeAddress($data, $organizer);
        $organizer = $this->denormalizeLabels($data, $organizer);
        $organizer = $this->denormalizeContactPoint($data, $organizer);
        $organizer = $this->denormalizeGeoCoordinates($data, $organizer);
        $organizer = $this->denormalizeImages($data, $organizer);
        return $this->denormalizeWorkflowStatus($data, $organizer);
    }

    private function denormalizeDescription(array $data, ImmutableOrganizer $organizer): ImmutableOrganizer
    {
        if (isset($data['description'])) {
            /* @var TranslatedDescription $description */
            $description = $this->descriptionDenormalizer->denormalize($data['description'], TranslatedDescription::class);
            $organizer = $organizer->withDescription($description);
        }
        return $organizer;
    }

    private function denormalizeEducationalDescription(array $data, ImmutableOrganizer $organizer): ImmutableOrganizer
    {
        if (isset($data['educationalDescription'])) {
            /* @var TranslatedDescription $educationalDescription */
            $educationalDescription = $this->descriptionDenormalizer->denormalize(
                $data['educationalDescription'],
                TranslatedDescription::class
            );
            $organizer = $organizer->withEducationalDescription($educationalDescription);
        }
        return $organizer;
    }

    private function denormalizeAddress(array $data, ImmutableOrganizer $organizer): ImmutableOrganizer
    {
        if (isset($data['address'])) {
            /* @var TranslatedAddress $address */
            $address = $this->addressDenormalizer->denormalize($data['address'], TranslatedAddress::class);
            $organizer = $organizer->withAddress($address);
        }

        return $organizer;
    }

    private function denormalizeLabels(array $data, ImmutableOrganizer $organizer): ImmutableOrganizer
    {
        $labels = $this->labelsDenormalizer->denormalize($data, Labels::class);
        return $organizer->withLabels($labels);
    }

    private function denormalizeGeoCoordinates(array $data, ImmutableOrganizer $organizer): ImmutableOrganizer
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

    protected function denormalizeContactPoint(array $data, ImmutableOrganizer $organizer): ImmutableOrganizer
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

    private function denormalizeImages(array $data, ImmutableOrganizer $organizer): ImmutableOrganizer
    {
        if (isset($data['images'])) {
            $images = $this->imagesDenormalizer->denormalize($data['images'], Images::class);
            $organizer = $organizer->withImages($images);
        }
        return $organizer;
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === ImmutableOrganizer::class || $type === Organizer::class;
    }

    private function denormalizeWorkflowStatus(array $data, ImmutableOrganizer $organizer): ImmutableOrganizer
    {
        if (isset($data['workflowStatus'])) {
            $workflowStatus = new WorkflowStatus($data['workflowStatus']);
            $organizer = $organizer->withWorkflowStatus($workflowStatus);
        }

        return $organizer;
    }
}
