<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\Event;

use CultuurNet\UDB3\Model\Event\Event;
use CultuurNet\UDB3\Model\Event\EventIDParser;
use CultuurNet\UDB3\Model\Event\ImmutableEvent;
use CultuurNet\UDB3\Model\Place\PlaceIDParser;
use CultuurNet\UDB3\Model\Place\PlaceReference;
use CultuurNet\UDB3\Model\Serializer\Offer\OfferDenormalizer;
use CultuurNet\UDB3\Model\Serializer\Place\PlaceDenormalizer;
use CultuurNet\UDB3\Model\Serializer\Place\PlaceReferenceDenormalizer;
use CultuurNet\UDB3\Model\Validation\Event\EventValidator;
use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUIDParser;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use Respect\Validation\Validator;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class EventDenormalizer extends OfferDenormalizer
{
    /**
     * @var Validator
     */
    private $eventValidator;

    /**
     * @var DenormalizerInterface
     */
    private $placeReferenceDenormalizer;

    public function __construct(
        Validator $eventValidator = null,
        UUIDParser $eventIDParser = null,
        DenormalizerInterface $titleDenormalizer = null,
        DenormalizerInterface $descriptionDenormalizer = null,
        DenormalizerInterface $calendarDenormalizer = null,
        DenormalizerInterface $categoriesDenormalizer = null,
        DenormalizerInterface $placeReferenceDenormalizer = null,
        DenormalizerInterface $labelsDenormalizer = null,
        DenormalizerInterface $organizerDenormalizer = null,
        DenormalizerInterface $ageRangeDenormalizer = null,
        DenormalizerInterface $priceInfoDenormalizer = null,
        DenormalizerInterface $bookingInfoDenormalizer = null,
        DenormalizerInterface $contactPointDenormalizer = null,
        DenormalizerInterface $mediaObjectReferencesDenormalizer = null
    ) {
        if (!$eventValidator) {
            $eventValidator = new EventValidator();
        }

        if (!$eventIDParser) {
            $eventIDParser = new EventIDParser();
        }

        if (!$placeReferenceDenormalizer) {
            $placeReferenceDenormalizer = new PlaceReferenceDenormalizer(
                new PlaceIDParser(),
                new PlaceDenormalizer()
            );
        }

        $this->eventValidator = $eventValidator;
        $this->placeReferenceDenormalizer = $placeReferenceDenormalizer;

        parent::__construct(
            $eventIDParser,
            $titleDenormalizer,
            $descriptionDenormalizer,
            $calendarDenormalizer,
            $categoriesDenormalizer,
            $labelsDenormalizer,
            $organizerDenormalizer,
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
        $placeReference = $this->placeReferenceDenormalizer->denormalize(
            $originalData['location'],
            PlaceReference::class
        );

        return new ImmutableEvent(
            $id,
            $mainLanguage,
            $title,
            $calendar,
            $placeReference,
            $categories
        );
    }

    /**
     * @inheritdoc
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (!$this->supportsDenormalization($data, $class, $format)) {
            throw new UnsupportedException("EventDenormalizer does not support {$class}.");
        }

        if (!is_array($data)) {
            throw new UnsupportedException('Event data should be an associative array.');
        }

        $this->eventValidator->assert($data);

        /* @var ImmutableEvent $offer */
        $offer = $this->denormalizeOffer($data);
        $offer = $this->denormalizeAudienceType($data, $offer);

        return $offer;
    }

    /**
     * @return ImmutableEvent
     */
    private function denormalizeAudienceType(array $data, ImmutableEvent $event)
    {
        if (isset($data['audience']['audienceType'])) {
            $audienceType = new AudienceType((string) $data['audience']['audienceType']);
            $event = $event->withAudienceType($audienceType);
        }

        return $event;
    }

    /**
     * @inheritdoc
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === ImmutableEvent::class || $type === Event::class;
    }
}
