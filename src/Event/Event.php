<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use Broadway\EventSourcing\EventSourcedAggregateRoot;
use CultuurNet\UDB3\Event\Events\AttendanceModeUpdated;
use CultuurNet\UDB3\Event\Events\AvailableFromUpdated;
use CultuurNet\UDB3\Event\Events\OnlineUrlUpdated;
use CultuurNet\UDB3\Event\Events\ThemeRemoved;
use CultuurNet\UDB3\Event\Events\VideoAdded;
use CultuurNet\UDB3\Event\Events\VideoDeleted;
use CultuurNet\UDB3\Event\Events\VideoUpdated;
use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarFactory;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\Cdb\UpdateableWithCdbXmlInterface;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\Events\AudienceUpdated;
use CultuurNet\UDB3\Event\Events\BookingInfoUpdated;
use CultuurNet\UDB3\Event\Events\CalendarUpdated;
use CultuurNet\UDB3\Event\Events\ContactPointUpdated;
use CultuurNet\UDB3\Event\Events\DescriptionTranslated;
use CultuurNet\UDB3\Event\Events\DescriptionUpdated;
use CultuurNet\UDB3\Event\Events\EventCdbXMLInterface;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventDeleted;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\EventUpdatedFromUDB2;
use CultuurNet\UDB3\Event\Events\FacilitiesUpdated;
use CultuurNet\UDB3\Event\Events\GeoCoordinatesUpdated;
use CultuurNet\UDB3\Event\Events\ImageAdded;
use CultuurNet\UDB3\Event\Events\ImageRemoved;
use CultuurNet\UDB3\Event\Events\Image\ImagesImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\Image\ImagesUpdatedFromUDB2;
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
use CultuurNet\UDB3\Event\Events\OrganizerDeleted;
use CultuurNet\UDB3\Event\Events\OrganizerUpdated;
use CultuurNet\UDB3\Event\Events\OwnerChanged;
use CultuurNet\UDB3\Event\Events\PriceInfoUpdated;
use CultuurNet\UDB3\Event\Events\ThemeUpdated;
use CultuurNet\UDB3\Event\Events\TitleTranslated;
use CultuurNet\UDB3\Event\Events\TitleUpdated;
use CultuurNet\UDB3\Event\Events\TypeUpdated;
use CultuurNet\UDB3\Event\Events\TypicalAgeRangeDeleted;
use CultuurNet\UDB3\Event\Events\TypicalAgeRangeUpdated;
use CultuurNet\UDB3\Event\ValueObjects\Audience;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Label as LegacyLabel;
use CultuurNet\UDB3\LabelCollection;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Model\ValueObject\Audience\Age;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEventUpdate;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Virtual\AttendanceMode;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Offer\AgeRange;
use CultuurNet\UDB3\Offer\CalendarTypeNotSupported;
use CultuurNet\UDB3\Offer\Events\AbstractOwnerChanged;
use CultuurNet\UDB3\Offer\Offer;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Offer\ValueObjects\BookingAvailability;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Timestamp;
use CultuurNet\UDB3\Title;
use DateTimeImmutable;
use DateTimeInterface;
use CultuurNet\UDB3\StringLiteral;

class Event extends Offer implements UpdateableWithCdbXmlInterface
{
    protected string $eventId;

    private string $attendanceMode = 'offline';

    private string $onlineUrl = '';

    private ?Audience $audience = null;

    private ?LocationId $locationId = null;

    private ?string $themeId = null;

    public static function getOfferType(): OfferType
    {
        return OfferType::event();
    }

    public static function create(
        string $eventId,
        Language $mainLanguage,
        Title $title,
        EventType $eventType,
        LocationId $location,
        Calendar $calendar,
        Theme $theme = null,
        DateTimeImmutable $publicationDate = null
    ): self {
        $event = new self();

        $event->apply(
            new EventCreated(
                $eventId,
                $mainLanguage,
                $title,
                $eventType,
                $location,
                $calendar,
                $theme,
                $publicationDate
            )
        );

        if ($location->isDummyPlaceForEducation()) {
            // Bookable education events should get education as their audience type. We record this explicitly so we
            // don't have to handle this edge case in every read model projector.
            $event->apply(
                new AudienceUpdated($eventId, new Audience(AudienceType::education()))
            );
        }

        return $event;
    }

    public function updateGeoCoordinates(Coordinates $coordinates): void
    {
        if ($this->locationId) {
            return;
        }

        parent::updateGeoCoordinates($coordinates);
    }

    public function copy(string $newEventId, Calendar $calendar): Event
    {
        if ($this->hasUncommittedEvents()) {
            throw new \RuntimeException('I refuse to copy, there are uncommitted events present.');
        }

        // The copied event will have a playhead of the original event + 1
        $copy = clone $this;

        $copy->apply(
            new EventCopied(
                $newEventId,
                $this->eventId,
                $calendar
            )
        );

        return $copy;
    }

    public static function importFromUDB2(
        string $eventId,
        string $cdbXml,
        string $cdbXmlNamespaceUri
    ): Event {
        $event = new self();
        $event->apply(
            new EventImportedFromUDB2(
                $eventId,
                $cdbXml,
                $cdbXmlNamespaceUri
            )
        );

        return $event;
    }

    public function getAggregateRootId(): string
    {
        return $this->eventId;
    }

    protected function applyEventCreated(EventCreated $eventCreated): void
    {
        $this->eventId = $eventCreated->getEventId();
        $this->titles[$eventCreated->getMainLanguage()->getCode()] = $eventCreated->getTitle();
        $this->calendar = $eventCreated->getCalendar();
        $this->audience = new Audience(AudienceType::everyone());
        $this->contactPoint = new ContactPoint();
        $this->bookingInfo = new BookingInfo();
        $this->typeId = $eventCreated->getEventType()->getId();
        $this->themeId = $eventCreated->getTheme() ? $eventCreated->getTheme()->getId() : null;
        $this->locationId = $eventCreated->getLocation();
        $this->mainLanguage = $eventCreated->getMainLanguage();
        $this->workflowStatus = WorkflowStatus::DRAFT();
    }

    protected function applyEventCopied(EventCopied $eventCopied): void
    {
        $this->eventId = $eventCopied->getItemId();
        $this->calendar = $eventCopied->getCalendar();
        $this->workflowStatus = WorkflowStatus::DRAFT();
        $this->labels = new LabelCollection();
    }

    protected function applyEventImportedFromUDB2(EventImportedFromUDB2 $eventImported): void
    {
        $this->eventId = $eventImported->getEventId();
        // When importing from UDB2 the default main language is always 'nl'.
        $this->mainLanguage = new Language('nl');
        $this->setUDB2Data($eventImported);
    }

    protected function applyEventUpdatedFromUDB2(EventUpdatedFromUDB2 $eventUpdated): void
    {
        // Note: when updating from UDB2 never change the main language.
        $this->setUDB2Data($eventUpdated);
    }

    protected function applyEventDeleted(EventDeleted $event): void
    {
        $this->workflowStatus = WorkflowStatus::DELETED();
    }

    protected function setUDB2Data(EventCdbXMLInterface $eventCdbXML): void
    {
        $udb2Event = EventItemFactory::createEventFromCdbXml(
            $eventCdbXML->getCdbXmlNamespaceUri(),
            $eventCdbXML->getCdbXml()
        );

        // Just clear the facilities.
        $this->facilities = [];

        // Just clear the location id after an import or update.
        $this->locationId = null;

        // Just clear the contact point.
        $this->contactPoint = null;

        // Correctly set the Calendar
        // We need this for future Status updates
        $calendarFactory = new CalendarFactory();
        $this->calendar = $calendarFactory->createFromCdbCalendar($udb2Event->getCalendar());

        // Correctly set the age range to avoid issues with deleting age range.
        // after an update from UDB2.
        $this->typicalAgeRange = new AgeRange(
            $udb2Event->getAgeFrom() ? new Age($udb2Event->getAgeFrom()) : null,
            $udb2Event->getAgeTo() ? new Age($udb2Event->getAgeTo()) : null
        );

        // Just clear the booking info.
        $this->bookingInfo = null;

        // Just clear the price info.
        $this->priceInfo = null;

        $this->importWorkflowStatus($udb2Event);
        $this->labels = LabelCollection::fromKeywords($udb2Event->getKeywords(true));
    }

    public function updateMajorInfo(
        Title $title,
        EventType $eventType,
        LocationId $location,
        Calendar $calendar,
        Theme $theme = null
    ): void {
        $this->apply(new MajorInfoUpdated($this->eventId, $title, $eventType, $location, $calendar, $theme));

        if ($location->isDummyPlaceForEducation()) {
            // Bookable education events should get education as their audience type. We record this explicitly so we
            // don't have to handle this edge case in every read model projector.
            $this->apply(
                new AudienceUpdated($this->eventId, new Audience(AudienceType::education()))
            );
        }
    }

    protected function applyMajorInfoUpdated(MajorInfoUpdated $majorInfoUpdated): void
    {
        $this->locationId = $majorInfoUpdated->getLocation();
        $this->calendar = $majorInfoUpdated->getCalendar();
    }

    public function updateLocation(LocationId $locationId): void
    {
        if (!is_null($this->locationId) && $this->locationId->sameAs($locationId)) {
            return;
        }

        if (!$locationId->isVirtualLocation() && $this->attendanceMode === AttendanceMode::online()->toString()) {
            throw new UpdateLocationNotSupported(
                'Instead of passing the real location for this online event, please update the attendance mode to offline or mixed.'
            );
        }

        $this->apply(new LocationUpdated($this->eventId, $locationId));

        if ($locationId->isDummyPlaceForEducation()) {
            // Bookable education events should get education as their audience type. We record this explicitly so we
            // don't have to handle this edge case in every read model projector.
            $this->apply(
                new AudienceUpdated($this->eventId, new Audience(AudienceType::education()))
            );
        }
    }

    public function applyLocationUpdated(LocationUpdated $locationUpdated): void
    {
        $this->locationId = $locationUpdated->getLocationId();
    }

    public function updateSubEvents(SubEventUpdate ...$subEventUpdates): void
    {
        $timestamps = $this->calendar->getTimestamps();

        if (empty($timestamps)) {
            throw CalendarTypeNotSupported::forCalendarType($this->calendar->getType());
        }

        foreach ($subEventUpdates as $subEventUpdate) {
            $index = $subEventUpdate->getSubEventId();

            if (!isset($timestamps[$index])) {
                // If the timestamp to update doesn't exist, it's most likely a concurrency issue.
                continue;
            }

            $timestamp = $timestamps[$index];

            $subEventStatus = $subEventUpdate->getStatus() ? Status::fromUdb3ModelStatus($subEventUpdate->getStatus()) : null;
            $subEventBookingAvailability = $subEventUpdate->getBookingAvailability() ? BookingAvailability::fromUdb3ModelBookingAvailability($subEventUpdate->getBookingAvailability()) : null;

            $updatedTimestamp = new Timestamp(
                $subEventUpdate->getStartDate() ?: $timestamp->getStartDate(),
                $subEventUpdate->getEndDate() ?: $timestamp->getEndDate(),
                $subEventStatus ?? $timestamp->getStatus(),
                $subEventBookingAvailability ?? $timestamp->getBookingAvailability()
            );

            $timestamps[$index] = $updatedTimestamp;
        }

        $updatedCalendar = new Calendar(
            $this->calendar->getType(),
            null,
            null,
            $timestamps,
            $this->calendar->getOpeningHours()
        );

        if (!$this->calendar->sameAs($updatedCalendar)) {
            $this->apply(
                new CalendarUpdated($this->eventId, $updatedCalendar)
            );
        }
    }

    public function updateAttendanceMode(AttendanceMode $attendanceMode): void
    {
        if ($this->attendanceMode !== $attendanceMode->toString()) {
            $this->apply(new AttendanceModeUpdated($this->eventId, $attendanceMode->toString()));
        }
    }

    public function applyAttendanceModeUpdated(AttendanceModeUpdated $attendanceModeUpdated): void
    {
        $this->attendanceMode = $attendanceModeUpdated->getAttendanceMode();
    }

    public function updateOnlineUrl(Url $onlineUrl): void
    {
        if ($this->onlineUrl !== $onlineUrl->toString()) {
            $this->apply(new OnlineUrlUpdated($this->eventId, $onlineUrl->toString()));
        }
    }

    public function applyOnlineUrlUpdated(OnlineUrlUpdated $onlineUrlUpdated): void
    {
        $this->onlineUrl = $onlineUrlUpdated->getOnlineUrl();
    }

    public function updateAudience(Audience $audience): void
    {
        $audienceType = $audience->getAudienceType();
        if ($this->locationId &&
            $this->locationId->isDummyPlaceForEducation() &&
            !$audienceType->sameAs(AudienceType::education())
        ) {
            throw IncompatibleAudienceType::forEvent($this->eventId, $audienceType);
        }

        if (is_null($this->audience) || !$this->audience->equals($audience)) {
            $this->apply(
                new AudienceUpdated(
                    $this->eventId,
                    $audience
                )
            );
        }
    }

    public function applyAudienceUpdated(AudienceUpdated $audienceUpdated): void
    {
        $this->audience= $audienceUpdated->getAudience();
    }

    public function updateTheme(Category $category): void
    {
        if (!$this->themeId || $this->themeId !== $category->getId()->toString()) {
            $this->apply(new ThemeUpdated($this->eventId, Theme::fromUdb3ModelCategory($category)));
        }
    }

    protected function applyThemeUpdated(ThemeUpdated $themeUpdated): void
    {
        $this->themeId = $themeUpdated->getTheme()->getId();
    }

    public function removeTheme(): void
    {
        if ($this->themeId === null) {
            return;
        }
        $this->apply(new ThemeRemoved($this->eventId));
    }

    protected function applyThemeRemoved(ThemeRemoved $themeRemoved): void
    {
        $this->themeId = null;
    }

    protected function createOwnerChangedEvent($newOwnerId): AbstractOwnerChanged
    {
        return new OwnerChanged($this->eventId, $newOwnerId);
    }

    protected function createImagesImportedFromUDB2(ImageCollection $images): ImagesImportedFromUDB2
    {
        return new ImagesImportedFromUDB2($this->eventId, $images);
    }

    protected function createImagesUpdatedFromUDB2(ImageCollection $images): ImagesUpdatedFromUDB2
    {
        return new ImagesUpdatedFromUDB2($this->eventId, $images);
    }

    public function updateWithCdbXml($cdbXml, $cdbXmlNamespaceUri): void
    {
        $this->apply(
            new EventUpdatedFromUDB2(
                $this->eventId,
                $cdbXml,
                $cdbXmlNamespaceUri
            )
        );
    }

    protected function createLabelAddedEvent(LegacyLabel $label): LabelAdded
    {
        return new LabelAdded($this->eventId, $label);
    }

    protected function createLabelRemovedEvent(LegacyLabel $label): LabelRemoved
    {
        return new LabelRemoved($this->eventId, $label);
    }

    protected function createLabelsImportedEvent(Labels $labels): LabelsImported
    {
        return new LabelsImported($this->eventId, $labels);
    }

    protected function createImageAddedEvent(Image $image): ImageAdded
    {
        return new ImageAdded($this->eventId, $image);
    }

    protected function createImageRemovedEvent(Image $image): ImageRemoved
    {
        return new ImageRemoved($this->eventId, $image);
    }

    protected function createImageUpdatedEvent(
        UUID $mediaObjectId,
        StringLiteral $description,
        CopyrightHolder $copyrightHolder,
        ?string $language = null
    ): ImageUpdated {
        return new ImageUpdated(
            $this->eventId,
            $mediaObjectId,
            $description,
            $copyrightHolder,
            $language
        );
    }

    protected function createMainImageSelectedEvent(Image $image): MainImageSelected
    {
        return new MainImageSelected($this->eventId, $image);
    }

    protected function createVideoAddedEvent(Video $video): VideoAdded
    {
        return new VideoAdded($this->eventId, $video);
    }

    protected function createVideoDeletedEvent(string $videoId): VideoDeleted
    {
        return new VideoDeleted($this->eventId, $videoId);
    }

    protected function createVideoUpdatedEvent(Video $video): VideoUpdated
    {
        return new VideoUpdated($this->eventId, $video);
    }

    protected function createTitleTranslatedEvent(Language $language, Title $title): TitleTranslated
    {
        return new TitleTranslated($this->eventId, $language, $title);
    }

    protected function createTitleUpdatedEvent(Title $title): TitleUpdated
    {
        return new TitleUpdated($this->eventId, $title);
    }

    protected function createDescriptionTranslatedEvent(Language $language, Description $description): DescriptionTranslated
    {
        return new DescriptionTranslated($this->eventId, $language, $description);
    }

    protected function createDescriptionUpdatedEvent(Description $description): DescriptionUpdated
    {
        return new DescriptionUpdated($this->eventId, $description);
    }

    protected function createCalendarUpdatedEvent(Calendar $calendar): CalendarUpdated
    {
        return new CalendarUpdated($this->eventId, $calendar);
    }

    protected function createTypicalAgeRangeUpdatedEvent(AgeRange $typicalAgeRange): TypicalAgeRangeUpdated
    {
        return new TypicalAgeRangeUpdated($this->eventId, $typicalAgeRange);
    }

    protected function createTypicalAgeRangeDeletedEvent(): TypicalAgeRangeDeleted
    {
        return new TypicalAgeRangeDeleted($this->eventId);
    }

    protected function createOrganizerUpdatedEvent(string $organizerId): OrganizerUpdated
    {
        return new OrganizerUpdated($this->eventId, $organizerId);
    }

    protected function createOrganizerDeletedEvent(string $organizerId): OrganizerDeleted
    {
        return new OrganizerDeleted($this->eventId, $organizerId);
    }

    protected function createContactPointUpdatedEvent(ContactPoint $contactPoint): ContactPointUpdated
    {
        return new ContactPointUpdated($this->eventId, $contactPoint);
    }

    protected function createGeoCoordinatesUpdatedEvent(Coordinates $coordinates): GeoCoordinatesUpdated
    {
        return new GeoCoordinatesUpdated($this->eventId, $coordinates);
    }

    protected function createBookingInfoUpdatedEvent(BookingInfo $bookingInfo): BookingInfoUpdated
    {
        return new BookingInfoUpdated($this->eventId, $bookingInfo);
    }

    protected function createPriceInfoUpdatedEvent(PriceInfo $priceInfo): PriceInfoUpdated
    {
        return new PriceInfoUpdated($this->eventId, $priceInfo);
    }

    protected function createOfferDeletedEvent(): EventDeleted
    {
        return new EventDeleted($this->eventId);
    }

    protected function createAvailableFromUpdatedEvent(DateTimeInterface $availableFrom): AvailableFromUpdated
    {
        return new AvailableFromUpdated($this->eventId, $availableFrom);
    }

    protected function createPublishedEvent(\DateTimeInterface $publicationDate): Published
    {
        return new Published($this->eventId, $publicationDate);
    }

    protected function createApprovedEvent(): Approved
    {
        return new Approved($this->eventId);
    }

    protected function createRejectedEvent(StringLiteral $reason): Rejected
    {
        return new Rejected($this->eventId, $reason);
    }

    protected function createFlaggedAsDuplicate(): FlaggedAsDuplicate
    {
        return new FlaggedAsDuplicate($this->eventId);
    }

    protected function createFlaggedAsInappropriate(): FlaggedAsInappropriate
    {
        return new FlaggedAsInappropriate($this->eventId);
    }

    protected function createTypeUpdatedEvent(EventType $type): TypeUpdated
    {
        return new TypeUpdated($this->eventId, $type);
    }

    protected function createFacilitiesUpdatedEvent(array $facilities): FacilitiesUpdated
    {
        return new FacilitiesUpdated($this->eventId, $facilities);
    }

    /**
     * Use reflection to get check if the aggregate has uncommitted events.
     */
    private function hasUncommittedEvents(): bool
    {
        $reflector = new \ReflectionClass(EventSourcedAggregateRoot::class);
        $property = $reflector->getProperty('uncommittedEvents');

        $property->setAccessible(true);
        $uncommittedEvents = $property->getValue($this);

        return !empty($uncommittedEvents);
    }
}
