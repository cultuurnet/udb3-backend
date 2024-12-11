<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\Event;

use CultuurNet\UDB3\Model\Event\Event;
use CultuurNet\UDB3\Model\Event\EventIDParser;
use CultuurNet\UDB3\Model\Event\ImmutableEvent;
use CultuurNet\UDB3\Model\Place\PlaceIDParser;
use CultuurNet\UDB3\Model\Place\PlaceReference;
use CultuurNet\UDB3\Model\Serializer\Offer\OfferDenormalizer;
use CultuurNet\UDB3\Model\Serializer\Place\PlaceReferenceDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Identity\UuidParser;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Online\AttendanceMode;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class EventDenormalizer extends OfferDenormalizer
{
    private DenormalizerInterface $placeReferenceDenormalizer;

    public function __construct(
        UuidParser $eventIDParser = null,
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
        DenormalizerInterface $mediaObjectReferencesDenormalizer = null,
        DenormalizerInterface $videoDenormalizer = null
    ) {
        if (!$eventIDParser) {
            $eventIDParser = new EventIDParser();
        }

        if (!$placeReferenceDenormalizer) {
            $placeReferenceDenormalizer = new PlaceReferenceDenormalizer(new PlaceIDParser());
        }

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
    ): ImmutableEvent {
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

    public function denormalize($data, $class, $format = null, array $context = []): ImmutableEvent
    {
        if (!$this->supportsDenormalization($data, $class, $format)) {
            throw new UnsupportedException("EventDenormalizer does not support {$class}.");
        }

        if (!is_array($data)) {
            throw new UnsupportedException('Event data should be an associative array.');
        }

        $offer = $this->denormalizeOffer($data);

        if (! $offer instanceof ImmutableEvent) {
            throw new UnsupportedException(sprintf('Expected an %s but got a %s', ImmutableEvent::class, get_class($offer)));
        }

        $offer = $this->denormalizeAttendanceMode($data, $offer);
        $offer = $this->denormalizeOnlineUrl($data, $offer);
        return $this->denormalizeAudienceType($data, $offer);
    }

    private function denormalizeAttendanceMode(array $data, ImmutableEvent $event): ImmutableEvent
    {
        if (isset($data['attendanceMode'])) {
            $event = $event->withAttendanceMode(new AttendanceMode($data['attendanceMode']));
        }

        return $event;
    }

    private function denormalizeOnlineUrl(array $data, ImmutableEvent $event): ImmutableEvent
    {
        if (isset($data['onlineUrl'])) {
            $event = $event->withOnlineUrl(new Url($data['onlineUrl']));
        }

        return $event;
    }

    private function denormalizeAudienceType(array $data, ImmutableEvent $event): ImmutableEvent
    {
        if (isset($data['audience']['audienceType'])) {
            $audienceType = new AudienceType((string) $data['audience']['audienceType']);
            $event = $event->withAudienceType($audienceType);
        }

        return $event;
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === ImmutableEvent::class || $type === Event::class;
    }
}
