<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use Broadway\EventSourcing\EventSourcedAggregateRoot;
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
use CultuurNet\UDB3\Event\Events\Concluded;
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
use CultuurNet\UDB3\Event\ValueObjects\AudienceType;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\LabelCollection;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Offer\AgeRange;
use CultuurNet\UDB3\Offer\Events\AbstractOwnerChanged;
use CultuurNet\UDB3\Offer\Offer;
use CultuurNet\UDB3\Offer\WorkflowStatus;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Timestamp;
use CultuurNet\UDB3\Title;
use DateTimeImmutable;
use ValueObjects\Identity\UUID;
use ValueObjects\Person\Age;
use ValueObjects\StringLiteral\StringLiteral;

class Event extends Offer implements UpdateableWithCdbXmlInterface
{
    /**
     * @var string
     */
    protected $eventId;

    /**
     * @var Audience
     */
    private $audience;

    /**
     * @var LocationId|null
     */
    private $locationId;

    /**
     * @var boolean
     */
    private $concluded = false;

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
                new AudienceUpdated($eventId, new Audience(AudienceType::EDUCATION()))
            );
        }

        return $event;
    }

    public function updateGeoCoordinates(Coordinates $coordinates)
    {
        if ($this->locationId) {
            return;
        }

        parent::updateGeoCoordinates($coordinates);
    }

    /**
     * @param string $newEventId
     *
     * @return Event
     */
    public function copy($newEventId, Calendar $calendar)
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

    /**
     * @param string $eventId
     * @param string $cdbXml
     * @param string $cdbXmlNamespaceUri
     * @return Event
     */
    public static function importFromUDB2(
        $eventId,
        $cdbXml,
        $cdbXmlNamespaceUri
    ) {
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

    /**
     * {@inheritdoc}
     */
    public function getAggregateRootId()
    {
        return $this->eventId;
    }

    protected function applyEventCreated(EventCreated $eventCreated)
    {
        $this->eventId = $eventCreated->getEventId();
        $this->titles[$eventCreated->getMainLanguage()->getCode()] = $eventCreated->getTitle();
        $this->calendar = $eventCreated->getCalendar();
        $this->audience = new Audience(AudienceType::EVERYONE());
        $this->contactPoint = new ContactPoint();
        $this->bookingInfo = new BookingInfo();
        $this->typeId = $eventCreated->getEventType()->getId();
        $this->themeId = $eventCreated->getTheme() ? $eventCreated->getTheme()->getId() : null;
        $this->locationId = $eventCreated->getLocation();
        $this->mainLanguage = $eventCreated->getMainLanguage();
        $this->workflowStatus = WorkflowStatus::DRAFT();
    }


    protected function applyEventCopied(EventCopied $eventCopied)
    {
        $this->eventId = $eventCopied->getItemId();
        $this->workflowStatus = WorkflowStatus::DRAFT();
        $this->labels = new LabelCollection();
    }

    protected function applyEventImportedFromUDB2(
        EventImportedFromUDB2 $eventImported
    ) {
        $this->eventId = $eventImported->getEventId();
        // When importing from UDB2 the default main language is always 'nl'.
        $this->mainLanguage = new Language('nl');
        $this->setUDB2Data($eventImported);
    }


    protected function applyEventUpdatedFromUDB2(
        EventUpdatedFromUDB2 $eventUpdated
    ) {
        // Note: when updating from UDB2 never change the main language.
        $this->setUDB2Data($eventUpdated);
    }

    protected function applyEventDeleted(EventDeleted $event): void
    {
        $this->isDeleted = true;
    }


    protected function setUDB2Data(
        EventCdbXMLInterface $eventCdbXML
    ) {
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

    /**
     * Update the major info.
     *
     */
    public function updateMajorInfo(
        Title $title,
        EventType $eventType,
        LocationId $location,
        Calendar $calendar,
        Theme $theme = null
    ) {
        $this->apply(new MajorInfoUpdated($this->eventId, $title, $eventType, $location, $calendar, $theme));

        if ($location->isDummyPlaceForEducation()) {
            // Bookable education events should get education as their audience type. We record this explicitly so we
            // don't have to handle this edge case in every read model projector.
            $this->apply(
                new AudienceUpdated($this->eventId, new Audience(AudienceType::EDUCATION()))
            );
        }
    }

    protected function applyMajorInfoUpdated(MajorInfoUpdated $majorInfoUpdated)
    {
        $this->locationId = $majorInfoUpdated->getLocation();
        $this->calendar = $majorInfoUpdated->getCalendar();
    }


    public function updateLocation(LocationId $locationId)
    {
        if (!is_null($this->locationId) && $this->locationId->sameValueAs($locationId)) {
            return;
        }

        $this->apply(new LocationUpdated($this->eventId, $locationId));

        if ($locationId->isDummyPlaceForEducation()) {
            // Bookable education events should get education as their audience type. We record this explicitly so we
            // don't have to handle this edge case in every read model projector.
            $this->apply(
                new AudienceUpdated($this->eventId, new Audience(AudienceType::EDUCATION()))
            );
        }
    }


    public function applyLocationUpdated(LocationUpdated $locationUpdated)
    {
        $this->locationId = $locationUpdated->getLocationId();
    }

    /**
     * @param Status[] $statuses
     *   List of event status updates to apply to the timestamps in the event's calendar.
     *   The statuses should be keyed by the index number of the timestamp(s) to update.
     * @see UpdateSubEventsStatus
     */
    public function updateSubEventsStatus(array $statuses): void
    {
        $timestamps = $this->calendar->getTimestamps();

        foreach ($statuses as $index => $status) {
            if (!isset($timestamps[$index])) {
                // If the timestamp to update doesn't exist, it's most likely a concurrency issue.
                continue;
            }

            $timestamp = $timestamps[$index];

            $updatedTimestamp = new Timestamp(
                $timestamp->getStartDate(),
                $timestamp->getEndDate(),
                $status
            );

            $timestamps[$index] = $updatedTimestamp;
        }

        $updatedCalendar = new Calendar(
            $this->calendar->getType(),
            $this->calendar->getStartDate(),
            $this->calendar->getEndDate(),
            $timestamps,
            $this->calendar->getOpeningHours()
        );

        if (!$this->calendar->sameAs($updatedCalendar)) {
            $this->apply(
                new CalendarUpdated($this->eventId, $updatedCalendar)
            );
        }
    }

    public function updateAudience(
        Audience $audience
    ): void {
        $audienceType = $audience->getAudienceType();
        if ($this->locationId &&
            $this->locationId->isDummyPlaceForEducation() &&
            !$audienceType->sameValueAs(AudienceType::EDUCATION())
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


    public function applyAudienceUpdated(AudienceUpdated $audienceUpdated)
    {
        $this->audience= $audienceUpdated->getAudience();
    }

    protected function createOwnerChangedEvent($newOwnerId): AbstractOwnerChanged
    {
        return new OwnerChanged($this->eventId, $newOwnerId);
    }

    /**
     * @inheritDoc
     * @return ImagesImportedFromUDB2
     */
    protected function createImagesImportedFromUDB2(ImageCollection $images)
    {
        return new ImagesImportedFromUDB2($this->eventId, $images);
    }

    /**
     * @inheritDoc
     * @return ImagesUpdatedFromUDB2
     */
    protected function createImagesUpdatedFromUDB2(ImageCollection $images)
    {
        return new ImagesUpdatedFromUDB2($this->eventId, $images);
    }

    /**
     * @inheritdoc
     */
    public function updateWithCdbXml($cdbXml, $cdbXmlNamespaceUri)
    {
        $this->apply(
            new EventUpdatedFromUDB2(
                $this->eventId,
                $cdbXml,
                $cdbXmlNamespaceUri
            )
        );
    }

    /**
     * @return LabelAdded
     */
    protected function createLabelAddedEvent(Label $label)
    {
        return new LabelAdded($this->eventId, $label);
    }

    /**
     * @return LabelRemoved
     */
    protected function createLabelRemovedEvent(Label $label)
    {
        return new LabelRemoved($this->eventId, $label);
    }

    /**
     * @inheritdoc
     */
    protected function createLabelsImportedEvent(Labels $labels)
    {
        return new LabelsImported($this->eventId, $labels);
    }

    /**
     * @return ImageAdded
     */
    protected function createImageAddedEvent(Image $image)
    {
        return new ImageAdded($this->eventId, $image);
    }

    /**
     * @return ImageRemoved
     */
    protected function createImageRemovedEvent(Image $image)
    {
        return new ImageRemoved($this->eventId, $image);
    }

    /**
     * @return ImageUpdated
     */
    protected function createImageUpdatedEvent(
        UUID $mediaObjectId,
        StringLiteral $description,
        CopyrightHolder $copyrightHolder
    ) {
        return new ImageUpdated(
            $this->eventId,
            $mediaObjectId,
            $description,
            $copyrightHolder
        );
    }

    /**
     * @return MainImageSelected
     */
    protected function createMainImageSelectedEvent(Image $image)
    {
        return new MainImageSelected($this->eventId, $image);
    }

    /**
     * @inheritdoc
     */
    protected function createTitleTranslatedEvent(Language $language, Title $title)
    {
        return new TitleTranslated($this->eventId, $language, $title);
    }

    /**
     * @return TitleUpdated
     */
    protected function createTitleUpdatedEvent(Title $title)
    {
        return new TitleUpdated($this->eventId, $title);
    }

    /**
     * @inheritdoc
     */
    protected function createDescriptionTranslatedEvent(Language $language, Description $description)
    {
        return new DescriptionTranslated($this->eventId, $language, $description);
    }

    /**
     * @inheritdoc
     */
    protected function createDescriptionUpdatedEvent(Description $description)
    {
        return new DescriptionUpdated($this->eventId, $description);
    }

    /**
     * @inheritdoc
     */
    protected function createCalendarUpdatedEvent(Calendar $calendar)
    {
        return new CalendarUpdated($this->eventId, $calendar);
    }

    /**
     * @param AgeRange $typicalAgeRange
     * @return TypicalAgeRangeUpdated
     */
    protected function createTypicalAgeRangeUpdatedEvent($typicalAgeRange)
    {
        return new TypicalAgeRangeUpdated($this->eventId, $typicalAgeRange);
    }

    /**
     * @return TypicalAgeRangeDeleted
     */
    protected function createTypicalAgeRangeDeletedEvent()
    {
        return new TypicalAgeRangeDeleted($this->eventId);
    }

    /**
     * @param string $organizerId
     * @return OrganizerUpdated
     */
    protected function createOrganizerUpdatedEvent($organizerId)
    {
        return new OrganizerUpdated($this->eventId, $organizerId);
    }

    /**
     * @param string $organizerId
     * @return OrganizerDeleted
     */
    protected function createOrganizerDeletedEvent($organizerId)
    {
        return new OrganizerDeleted($this->eventId, $organizerId);
    }

    /**
     * @return ContactPointUpdated
     */
    protected function createContactPointUpdatedEvent(ContactPoint $contactPoint)
    {
        return new ContactPointUpdated($this->eventId, $contactPoint);
    }

    /**
     * @inheritdoc
     */
    protected function createGeoCoordinatesUpdatedEvent(Coordinates $coordinates)
    {
        return new GeoCoordinatesUpdated($this->eventId, $coordinates);
    }

    /**
     * @return BookingInfoUpdated
     */
    protected function createBookingInfoUpdatedEvent(BookingInfo $bookingInfo)
    {
        return new BookingInfoUpdated($this->eventId, $bookingInfo);
    }

    /**
     * @return PriceInfoUpdated
     */
    protected function createPriceInfoUpdatedEvent(PriceInfo $priceInfo)
    {
        return new PriceInfoUpdated($this->eventId, $priceInfo);
    }

    /**
     * @return EventDeleted
     */
    protected function createOfferDeletedEvent()
    {
        return new EventDeleted($this->eventId);
    }

    /**
     * @inheritdoc
     */
    protected function createPublishedEvent(\DateTimeInterface $publicationDate)
    {
        return new Published($this->eventId, $publicationDate);
    }

    /**
     * @inheritdoc
     */
    protected function createApprovedEvent()
    {
        return new Approved($this->eventId);
    }

    /**
     * @inheritdoc
     */
    protected function createRejectedEvent(StringLiteral $reason)
    {
        return new Rejected($this->eventId, $reason);
    }

    /**
     * @inheritDoc
     */
    protected function createFlaggedAsDuplicate()
    {
        return new FlaggedAsDuplicate($this->eventId);
    }

    /**
     * @inheritDoc
     */
    protected function createFlaggedAsInappropriate()
    {
        return new FlaggedAsInappropriate($this->eventId);
    }

    /**
     * @inheritdoc
     */
    protected function createTypeUpdatedEvent(EventType $type)
    {
        return new TypeUpdated($this->eventId, $type);
    }

    /**
     * @inheritdoc
     */
    protected function createThemeUpdatedEvent(Theme $theme)
    {
        return new ThemeUpdated($this->eventId, $theme);
    }

    /**
     * @inheritdoc
     */
    protected function createFacilitiesUpdatedEvent(array $facilities)
    {
        return new FacilitiesUpdated($this->eventId, $facilities);
    }

    /**
     * Use reflection to get check if the aggregate has uncommitted events.
     * @return bool
     */
    private function hasUncommittedEvents()
    {
        $reflector = new \ReflectionClass(EventSourcedAggregateRoot::class);
        $property = $reflector->getProperty('uncommittedEvents');

        $property->setAccessible(true);
        $uncommittedEvents = $property->getValue($this);

        return !empty($uncommittedEvents);
    }

    public function conclude()
    {
        if (!$this->concluded) {
            $this->apply(new Concluded($this->eventId));
        }
    }


    protected function applyConcluded(Concluded $concluded)
    {
        $this->concluded = true;
    }
}
