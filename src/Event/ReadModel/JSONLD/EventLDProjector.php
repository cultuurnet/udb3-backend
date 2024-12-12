<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Calendar\Calendar as LegacyCalendar;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\Completeness\Completeness;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Event\Events\AttendanceModeUpdated;
use CultuurNet\UDB3\Event\Events\AudienceUpdated;
use CultuurNet\UDB3\Event\Events\AvailableFromUpdated;
use CultuurNet\UDB3\Event\Events\BookingInfoUpdated;
use CultuurNet\UDB3\Event\Events\CalendarUpdated;
use CultuurNet\UDB3\Event\Events\ContactPointUpdated;
use CultuurNet\UDB3\Event\Events\DescriptionDeleted;
use CultuurNet\UDB3\Event\Events\DescriptionTranslated;
use CultuurNet\UDB3\Event\Events\DescriptionUpdated;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventDeleted;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\EventUpdatedFromUDB2;
use CultuurNet\UDB3\Event\Events\FacilitiesUpdated;
use CultuurNet\UDB3\Event\Events\GeoCoordinatesUpdated;
use CultuurNet\UDB3\Event\Events\Image\ImagesImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\Image\ImagesUpdatedFromUDB2;
use CultuurNet\UDB3\Event\Events\ImageAdded;
use CultuurNet\UDB3\Event\Events\ImageRemoved;
use CultuurNet\UDB3\Event\Events\ImageUpdated;
use CultuurNet\UDB3\Event\Events\LabelAdded;
use CultuurNet\UDB3\Event\Events\LabelRemoved;
use CultuurNet\UDB3\Event\Events\LabelsImported;
use CultuurNet\UDB3\Event\Events\LocationUpdated;
use CultuurNet\UDB3\Event\Events\MainImageSelected;
use CultuurNet\UDB3\Event\Events\MajorInfoUpdated;
use CultuurNet\UDB3\Event\Events\Moderation\Approved;
use CultuurNet\UDB3\Event\Events\Moderation\FlaggedAsDuplicate;
use CultuurNet\UDB3\Event\Events\Moderation\FlaggedAsInappropriate;
use CultuurNet\UDB3\Event\Events\Moderation\Published;
use CultuurNet\UDB3\Event\Events\Moderation\Rejected;
use CultuurNet\UDB3\Event\Events\OnlineUrlDeleted;
use CultuurNet\UDB3\Event\Events\OnlineUrlUpdated;
use CultuurNet\UDB3\Event\Events\OrganizerDeleted;
use CultuurNet\UDB3\Event\Events\OrganizerUpdated;
use CultuurNet\UDB3\Event\Events\OwnerChanged;
use CultuurNet\UDB3\Event\Events\PriceInfoUpdated;
use CultuurNet\UDB3\Event\Events\ThemeRemoved;
use CultuurNet\UDB3\Event\Events\ThemeUpdated;
use CultuurNet\UDB3\Event\Events\TitleTranslated;
use CultuurNet\UDB3\Event\Events\TitleUpdated;
use CultuurNet\UDB3\Event\Events\TypeUpdated;
use CultuurNet\UDB3\Event\Events\TypicalAgeRangeDeleted;
use CultuurNet\UDB3\Event\Events\TypicalAgeRangeUpdated;
use CultuurNet\UDB3\Event\Events\VideoAdded;
use CultuurNet\UDB3\Event\Events\VideoDeleted;
use CultuurNet\UDB3\Event\Events\VideoUpdated;
use CultuurNet\UDB3\Event\EventTypeResolver;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Media\Serialization\MediaObjectSerializer;
use CultuurNet\UDB3\Model\Place\ImmutablePlace;
use CultuurNet\UDB3\Model\Serializer\Place\NilLocationNormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Audience\AudienceTypeNormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\CalendarNormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Taxonomy\Category\CategoryNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Moderation\AvailableTo;
use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject\VideoNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Online\AttendanceMode;
use CultuurNet\UDB3\Offer\Events\AbstractCalendarUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractTypeUpdated;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactoryInterface;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\OfferLDProjector;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\OfferUpdate;
use CultuurNet\UDB3\Place\LocalPlaceService;
use CultuurNet\UDB3\Place\PlaceTypeResolver;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\ReadModel\JsonDocumentMetaDataEnricherInterface;
use CultuurNet\UDB3\RecordedOn;
use CultuurNet\UDB3\SameAsForUitInVlaanderen;
use DateTimeInterface;
use JsonException;

final class EventLDProjector extends OfferLDProjector implements
    EventListener,
    PlaceServiceInterface
{
    private IriGeneratorInterface $placeIriGenerator;

    protected LocalPlaceService $placeService;

    protected IriOfferIdentifierFactoryInterface $iriOfferIdentifierFactory;

    protected CdbXMLImporter $cdbXMLImporter;

    private EventTypeResolver $eventTypeResolver;

    private PlaceTypeResolver $placeTypeResolver;

    private SameAsForUitInVlaanderen $sameAs;

    /**
     * @param string[] $basePriceTranslations
     */
    public function __construct(
        DocumentRepository $repository,
        IriGeneratorInterface $iriGenerator,
        IriGeneratorInterface $placeIriGenerator,
        IriGeneratorInterface $organizerIriGenerator,
        LocalPlaceService $placeService,
        DocumentRepository $organizerRepository,
        MediaObjectSerializer $mediaObjectSerializer,
        IriOfferIdentifierFactoryInterface $iriOfferIdentifierFactory,
        CdbXMLImporter $cdbXMLImporter,
        JsonDocumentMetaDataEnricherInterface $jsonDocumentMetaDataEnricher,
        EventTypeResolver $eventTypeResolver,
        array $basePriceTranslations,
        VideoNormalizer $videoNormalizer,
        Completeness $completeness
    ) {
        parent::__construct(
            $repository,
            $iriGenerator,
            $organizerIriGenerator,
            $organizerRepository,
            $mediaObjectSerializer,
            $jsonDocumentMetaDataEnricher,
            $basePriceTranslations,
            $videoNormalizer,
            $completeness
        );

        $this->placeIriGenerator = $placeIriGenerator;

        $this->placeService = $placeService;
        $this->cdbXMLImporter = $cdbXMLImporter;

        $this->iriOfferIdentifierFactory = $iriOfferIdentifierFactory;
        $this->eventTypeResolver = $eventTypeResolver;
        $this->placeTypeResolver = new PlaceTypeResolver();
        $this->sameAs = new SameAsForUitInVlaanderen();
    }

    protected function newDocument(string $id): JsonDocument
    {
        $document = new JsonDocument($id);

        $offerLd = $document->getBody();
        $offerLd->{'@id'} = $this->iriGenerator->iri($id);
        $offerLd->{'@context'} = '/contexts/event';

        return $document->withBody($offerLd);
    }

    protected function applyEventImportedFromUDB2(
        EventImportedFromUDB2 $eventImportedFromUDB2
    ): JsonDocument {
        return $this->applyEventCdbXmlFromUDB2(
            $eventImportedFromUDB2->getEventId(),
            $eventImportedFromUDB2->getCdbXmlNamespaceUri(),
            $eventImportedFromUDB2->getCdbXml()
        );
    }

    protected function applyEventUpdatedFromUDB2(
        EventUpdatedFromUDB2 $eventUpdatedFromUDB2
    ): JsonDocument {
        return $this->applyEventCdbXmlFromUDB2(
            $eventUpdatedFromUDB2->getEventId(),
            $eventUpdatedFromUDB2->getCdbXmlNamespaceUri(),
            $eventUpdatedFromUDB2->getCdbXml()
        );
    }

    protected function applyEventCdbXmlFromUDB2(
        string $eventId,
        string $cdbXmlNamespaceUri,
        string $cdbXml
    ): JsonDocument {
        $document = $this->newDocument($eventId);
        $eventLd = $this->projectEventCdbXmlToObject(
            $document->getBody(),
            $eventId,
            $cdbXmlNamespaceUri,
            $cdbXml
        );
        return $document->withBody($eventLd);
    }

    protected function projectEventCdbXmlToObject(
        \stdClass $jsonLd,
        string $eventId,
        string $cdbXmlNamespaceUri,
        string $cdbXml
    ): \stdClass {
        $udb2Event = EventItemFactory::createEventFromCdbXml(
            $cdbXmlNamespaceUri,
            $cdbXml
        );

        $jsonLd = $this->cdbXMLImporter->documentWithCdbXML(
            $jsonLd,
            $udb2Event,
            $this,
            $this,
            $this->slugger
        );

        // When importing from UDB2 the main language is always nl.
        // When updating from UDB2 never change the main language.
        if (!isset($jsonLd->mainLanguage)) {
            $this->setMainLanguage($jsonLd, new Language('nl'));
        }

        return $jsonLd;
    }

    protected function applyEventCreated(
        EventCreated $eventCreated,
        DomainMessage $domainMessage
    ): JsonDocument {
        $document = $this->newDocument($eventCreated->getEventId());
        $jsonLD = $document->getBody();

        $jsonLD->{'@id'} = $this->iriGenerator->iri(
            $eventCreated->getEventId()
        );

        $this->setMainLanguage(
            $jsonLD,
            new Language($eventCreated->getMainLanguage()->getCode())
        );

        $jsonLD->name[$eventCreated->getMainLanguage()->getCode()] = $eventCreated->getTitle();
        $jsonLD->location = [
                '@type' => 'Place',
            ] + $this->placeJSONLD(
                $eventCreated->getLocation()->toString()
            );

        $calendar = $eventCreated->getCalendar();
        $calendarJsonLD = (new CalendarNormalizer())->normalize($calendar);
        $jsonLD = (object) array_merge((array) $jsonLD, $calendarJsonLD);

        $jsonLD->availableTo = AvailableTo::createFromLegacyCalendar(
            LegacyCalendar::fromUdb3ModelCalendar($eventCreated->getCalendar()),
            $eventCreated->getEventType()
        )->format(DateTimeInterface::ATOM);

        // Same as.
        $jsonLD->sameAs = $this->sameAs->generateSameAs(
            $eventCreated->getEventId(),
            reset($jsonLD->name)
        );

        $eventType = $eventCreated->getEventType();
        $jsonLD->terms = [
            (new CategoryNormalizer())->normalize($eventType),
        ];

        $theme = $eventCreated->getTheme();
        if (!empty($theme)) {
            $jsonLD->terms[] = (new CategoryNormalizer())->normalize($theme);
        }

        $created = RecordedOn::fromDomainMessage($domainMessage);
        $jsonLD->created = $created->toString();
        $jsonLD->modified = $created->toString();

        // Set the creator.
        $author = $this->getAuthorFromMetadata($domainMessage->getMetadata());
        if ($author) {
            $jsonLD->creator = $author;
        }

        $jsonLD->workflowStatus = WorkflowStatus::DRAFT()->toString();

        $jsonLD->attendanceMode = AttendanceMode::offline()->toString();

        $jsonLD->typicalAgeRange = '-';

        $jsonLD->audience = (new AudienceTypeNormalizer())->normalize(AudienceType::everyone());

        return $document->withBody($jsonLD);
    }

    protected function applyEventCopied(
        EventCopied $eventCopied,
        DomainMessage $domainMessage
    ): JsonDocument {
        $originalDocument = $this->repository->fetch($eventCopied->getOriginalEventId());
        $eventJsonLD = $originalDocument->getBody();

        // Set the created and modified date.
        $created = RecordedOn::fromDomainMessage($domainMessage);
        $eventJsonLD->created = $created->toString();
        $eventJsonLD->modified = $created->toString();

        // Set the creator.
        $author = $this->getAuthorFromMetadata($domainMessage->getMetadata());
        if ($author) {
            $eventJsonLD->creator = $author;
        }

        // Set the id.
        $eventJsonLD->{'@id'} = $this->iriGenerator->iri($eventCopied->getItemId());

        // Set the new calendar.
        /** @var LegacyCalendar $calendar */
        $calendar = $eventCopied->getCalendar();
        $calendarJsonLD = $calendar->toJsonLd();

        $eventJsonLD->sameAs = $this->sameAs->generateSameAs(
            $eventCopied->getItemId(),
            (string) reset($eventJsonLD->name)
        );

        $eventJsonLD = (object) array_merge(
            (array) $eventJsonLD,
            $calendarJsonLD
        );

        // workaround - if user removes week scheme values, we need to explicitly
        // unset the field, because it holds the "old"(original) week scheme values
        if ($this->isPeriodicCalendarWithoutWeekScheme($eventCopied->getCalendar())) {
            unset($eventJsonLD->openingHours);
        }

        // Set workflow status.
        $eventJsonLD->workflowStatus = WorkflowStatus::DRAFT()->toString();

        // Remove labels.
        unset($eventJsonLD->labels);
        unset($eventJsonLD->hiddenLabels);

        // Set available to and from.
        $eventJsonLD->availableTo = AvailableTo::createFromLegacyCalendar(
            $eventCopied->getCalendar(),
            $this->getEventType($eventJsonLD)
        )->format(DateTimeInterface::ATOM);
        unset($eventJsonLD->availableFrom);

        $newDocument = new JsonDocument($eventCopied->getItemId());
        return $newDocument->withBody($eventJsonLD);
    }

    protected function applyEventDeleted(EventDeleted $eventDeleted): JsonDocument
    {
        $document = $this->loadDocumentFromRepository($eventDeleted);

        $jsonLD = $document->getBody();

        $jsonLD->workflowStatus = WorkflowStatus::DELETED()->toString();

        return $document->withBody($jsonLD);
    }

    protected function applyCalendarUpdated(AbstractCalendarUpdated $calendarUpdated): JsonDocument
    {
        $document = $this->loadDocumentFromRepository($calendarUpdated)
            ->apply(OfferUpdate::calendar($calendarUpdated->getCalendar()));

        $offerLd = $document->getBody();

        $offerLd->availableTo = AvailableTo::createFromLegacyCalendar(
            $calendarUpdated->getCalendar(),
            $this->getEventType($offerLd)
        )->format(DateTimeInterface::ATOM);

        return $document->withBody($offerLd);
    }

    protected function applyTypeUpdated(AbstractTypeUpdated $typeUpdated): JsonDocument
    {
        $document = $this->loadDocumentFromRepository($typeUpdated);
        $offerLd = $document->getBody();

        if (EventTypeResolver::isOnlyAvailableUntilStartDate($typeUpdated->getType())) {
            $offerLd->availableTo = $offerLd->startDate ?? $offerLd->availableTo;
        } else {
            $offerLd->availableTo = $offerLd->endDate ?? $offerLd->availableTo;
        }
        return $this->updateTerm($document->withBody($offerLd), $typeUpdated->getType());
    }

    private function getEventType(\stdClass $eventJsonLD): ?Category
    {
        if (!isset($eventJsonLD->terms)) {
            return null;
        }

        $eventType = null;
        foreach ($eventJsonLD->terms as $term) {
            if ($term->domain === 'eventtype') {
                $typeId = $term->id;

                // This is a workaround to allow copies of events that
                // have a placeType instead of an eventType.
                // These events could also be cleaned up in the future
                // @see https://jira.uitdatabank.be/browse/III-3926
                try {
                    $eventType = $this->eventTypeResolver->byId($typeId);
                } catch (\Exception $exception) {
                    $eventType = $this->placeTypeResolver->byId($typeId);
                }
            }
        }

        return $eventType;
    }

    protected function applyMajorInfoUpdated(MajorInfoUpdated $majorInfoUpdated): JsonDocument
    {
        $document = $this
            ->loadDocumentFromRepository($majorInfoUpdated)
            ->apply(OfferUpdate::calendar(LegacyCalendar::fromUdb3ModelCalendar($majorInfoUpdated->getCalendar())));

        $jsonLD = $document->getBody();

        $jsonLD->name->{$this->getMainLanguage($jsonLD)->getCode()} = $majorInfoUpdated->getTitle();

        $jsonLD->location = [
          '@type' => 'Place',
        ] + $this->placeJSONLD($majorInfoUpdated->getLocation()->toString());

        $jsonLD = $this->setAttendanceMode($jsonLD, $majorInfoUpdated->getLocation());

        $jsonLD->availableTo = AvailableTo::createFromLegacyCalendar(
            LegacyCalendar::fromUdb3ModelCalendar($majorInfoUpdated->getCalendar()),
            $majorInfoUpdated->getEventType()
        )->format(DateTimeInterface::ATOM);

        // Remove old theme and event type.
        $jsonLD->terms = array_filter(
            $jsonLD->terms,
            function ($term) {
                return $term->domain !== CategoryDomain::eventType()->toString()
                    && $term->domain !== CategoryDomain::theme()->toString();
            }
        );
        $jsonLD->terms = array_values($jsonLD->terms);

        $eventType = $majorInfoUpdated->getEventType();
        $jsonLD->terms[] = (new CategoryNormalizer())->normalize($eventType);

        $theme = $majorInfoUpdated->getTheme();
        if (!empty($theme)) {
            $jsonLD->terms[] = (new CategoryNormalizer())->normalize($theme);
        }

        return $document->withBody($jsonLD);
    }

    private function setAttendanceMode(\stdClass $jsonLD, LocationId $locationId): \stdClass
    {
        if ($locationId->isNilLocation()) {
            $jsonLD->attendanceMode = AttendanceMode::online()->toString();
            return $jsonLD;
        }

        if (!empty($jsonLD->onlineUrl)) {
            $jsonLD->attendanceMode = AttendanceMode::mixed()->toString();
            return $jsonLD;
        }

        $jsonLD->attendanceMode = AttendanceMode::offline()->toString();
        return $jsonLD;
    }

    public function applyLocationUpdated(LocationUpdated $locationUpdated): JsonDocument
    {
        $document = $this->loadDocumentFromRepository($locationUpdated);

        $jsonLD = $document->getBody();

        $jsonLD->location = [
            '@type' => 'Place',
         ] + $this->placeJSONLD($locationUpdated->getLocationId()->toString());

        return $document->withBody($jsonLD);
    }

    protected function applyGeoCoordinatesUpdated(GeoCoordinatesUpdated $geoCoordinatesUpdated): JsonDocument
    {
        $document = $this->loadDocumentFromRepositoryByItemId($geoCoordinatesUpdated->getItemId());

        $eventLd = $document->getBody();

        $eventLd->location->geo = (object) [
            'latitude' => $geoCoordinatesUpdated->getCoordinates()->getLatitude()->toFloat(),
            'longitude' => $geoCoordinatesUpdated->getCoordinates()->getLongitude()->toFloat(),
        ];

        return $document->withBody($eventLd);
    }

    protected function applyAttendanceModeUpdated(AttendanceModeUpdated $attendanceModeUpdated): JsonDocument
    {
        $document = $this->loadDocumentFromRepositoryByItemId($attendanceModeUpdated->getEventId());
        $jsonLD = $document->getBody();

        $jsonLD->attendanceMode = $attendanceModeUpdated->getAttendanceMode();

        return $document->withBody($jsonLD);
    }

    protected function applyOnlineUrlUpdated(OnlineUrlUpdated $onlineUrlUpdated): JsonDocument
    {
        $document = $this->loadDocumentFromRepositoryByItemId($onlineUrlUpdated->getEventId());
        $jsonLD = $document->getBody();

        $jsonLD->onlineUrl = $onlineUrlUpdated->getOnlineUrl();

        return $document->withBody($jsonLD);
    }

    protected function applyOnlineUrlDeleted(OnlineUrlDeleted $onlineUrlDeleted): JsonDocument
    {
        $document = $this->loadDocumentFromRepositoryByItemId($onlineUrlDeleted->getEventId());
        $jsonLD = $document->getBody();

        unset($jsonLD->onlineUrl);

        return $document->withBody($jsonLD);
    }

    protected function applyAudienceUpdated(AudienceUpdated $audienceUpdated): JsonDocument
    {
        $document = $this->loadDocumentFromRepository($audienceUpdated);
        $jsonLD = $document->getBody();

        $jsonLD->audience = (new AudienceTypeNormalizer())->normalize($audienceUpdated->getAudienceType());

        return $document->withBody($jsonLD);
    }

    protected function applyThemeUpdated(ThemeUpdated $themeUpdated): JsonDocument
    {
        $document = $this->loadDocumentFromRepository($themeUpdated);
        return $this->updateTerm($document, $themeUpdated->getTheme());
    }

    protected function applyThemeRemoved(ThemeRemoved $themeRemoved): JsonDocument
    {
        $document = $this->loadDocumentFromRepository($themeRemoved);
        $offerLD = $document->getBody();

        $existingTerms = property_exists($offerLD, 'terms') ? $offerLD->terms : [];

        $termsWithoutTheme = array_filter(
            $existingTerms,
            function ($term) {
                return !property_exists($term, 'domain') || $term->domain !== 'theme';
            }
        );

        $offerLD->terms = array_values($termsWithoutTheme);

        return $document->withBody($offerLD);
    }

    protected function applyOwnerChanged(OwnerChanged $ownerChanged): JsonDocument
    {
        return $this->loadDocumentFromRepositoryByItemId($ownerChanged->getOfferId())
            ->applyAssoc(
                function (array $jsonLd) use ($ownerChanged) {
                    $jsonLd['creator'] = $ownerChanged->getNewOwnerId();
                    return $jsonLd;
                }
            );
    }

    public function placeJSONLD(string $placeId): array
    {
        if (empty($placeId)) {
            return [];
        }

        if ((new LocationId($placeId))->isNilLocation()) {
            return $this->nilLocationJSONLD();
        }

        try {
            $placeJSONLD = $this->placeService->getEntity($placeId);
            return (array)Json::decode($placeJSONLD);
        } catch (JsonException $e) {
            return [];
        } catch (EntityNotFoundException $e) {
            // In case the place can not be found at the moment, just add its ID
            return [
                '@id' => $this->placeService->iri($placeId),
            ];
        }
    }

    private function nilLocationJSONLD(): array
    {
        return (new NilLocationNormalizer($this->placeIriGenerator))
            ->normalize(ImmutablePlace::createNilLocation());
    }

    private function getAuthorFromMetadata(Metadata $metadata): ?string
    {
        $properties = $metadata->serialize();

        return $properties['user_id'] ?? null;
    }

    protected function getLabelAddedClassName(): string
    {
        return LabelAdded::class;
    }

    protected function getLabelRemovedClassName(): string
    {
        return LabelRemoved::class;
    }

    protected function getLabelsImportedClassName(): string
    {
        return LabelsImported::class;
    }

    protected function getImageAddedClassName(): string
    {
        return ImageAdded::class;
    }

    protected function getImageRemovedClassName(): string
    {
        return ImageRemoved::class;
    }

    protected function getImageUpdatedClassName(): string
    {
        return ImageUpdated::class;
    }

    protected function getMainImageSelectedClassName(): string
    {
        return MainImageSelected::class;
    }

    protected function getVideoAddedClassName(): string
    {
        return VideoAdded::class;
    }

    protected function getVideoDeletedClassName(): string
    {
        return VideoDeleted::class;
    }

    protected function getVideoUpdatedClassName(): string
    {
        return VideoUpdated::class;
    }

    protected function getTitleTranslatedClassName(): string
    {
        return TitleTranslated::class;
    }

    protected function getDescriptionTranslatedClassName(): string
    {
        return DescriptionTranslated::class;
    }

    protected function getOrganizerUpdatedClassName(): string
    {
        return OrganizerUpdated::class;
    }

    protected function getOrganizerDeletedClassName(): string
    {
        return OrganizerDeleted::class;
    }

    protected function getBookingInfoUpdatedClassName(): string
    {
        return BookingInfoUpdated::class;
    }

    protected function getPriceInfoUpdatedClassName(): string
    {
        return PriceInfoUpdated::class;
    }

    protected function getContactPointUpdatedClassName(): string
    {
        return ContactPointUpdated::class;
    }

    protected function getDescriptionUpdatedClassName(): string
    {
        return DescriptionUpdated::class;
    }

    protected function getDescriptionDeletedClassName(): string
    {
        return DescriptionDeleted::class;
    }

    protected function getCalendarUpdatedClassName(): string
    {
        return CalendarUpdated::class;
    }

    protected function getTypicalAgeRangeUpdatedClassName(): string
    {
        return TypicalAgeRangeUpdated::class;
    }

    protected function getTypicalAgeRangeDeletedClassName(): string
    {
        return TypicalAgeRangeDeleted::class;
    }

    protected function getAvailableFromUpdatedClassName(): string
    {
        return AvailableFromUpdated::class;
    }

    protected function getPublishedClassName(): string
    {
        return Published::class;
    }

    protected function getApprovedClassName(): string
    {
        return Approved::class;
    }

    protected function getRejectedClassName(): string
    {
        return Rejected::class;
    }

    protected function getFlaggedAsDuplicateClassName(): string
    {
        return FlaggedAsDuplicate::class;
    }

    protected function getFlaggedAsInappropriateClassName(): string
    {
        return FlaggedAsInappropriate::class;
    }

    protected function getImagesImportedFromUdb2ClassName(): string
    {
        return ImagesImportedFromUDB2::class;
    }

    protected function getImagesUpdatedFromUdb2ClassName(): string
    {
        return ImagesUpdatedFromUDB2::class;
    }

    protected function getTitleUpdatedClassName(): string
    {
        return TitleUpdated::class;
    }

    protected function getTypeUpdatedClassName(): string
    {
        return TypeUpdated::class;
    }

    protected function getFacilitiesUpdatedClassName(): string
    {
        return FacilitiesUpdated::class;
    }

    protected function isPeriodicCalendarWithoutWeekScheme(LegacyCalendar $calendar): bool
    {
        return $calendar->getType()->sameAs(CalendarType::periodic())
            && $calendar->getOpeningHours() === [];
    }
}
