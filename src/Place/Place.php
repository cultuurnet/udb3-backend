<?php

namespace CultuurNet\UDB3\Place;

use CultuurNet\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarInterface;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Cdb\UpdateableWithCdbXmlInterface;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\LabelCollection;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\AgeRange;
use CultuurNet\UDB3\Offer\Offer;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Offer\WorkflowStatus;
use CultuurNet\UDB3\Place\Events\AddressTranslated;
use CultuurNet\UDB3\Place\Events\AddressUpdated;
use CultuurNet\UDB3\Place\Events\BookingInfoUpdated;
use CultuurNet\UDB3\Place\Events\CalendarUpdated;
use CultuurNet\UDB3\Place\Events\ContactPointUpdated;
use CultuurNet\UDB3\Place\Events\DescriptionTranslated;
use CultuurNet\UDB3\Place\Events\DescriptionUpdated;
use CultuurNet\UDB3\Place\Events\FacilitiesUpdated;
use CultuurNet\UDB3\Place\Events\GeoCoordinatesUpdated;
use CultuurNet\UDB3\Place\Events\Image\ImagesImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\Image\ImagesUpdatedFromUDB2;
use CultuurNet\UDB3\Place\Events\ImageAdded;
use CultuurNet\UDB3\Place\Events\ImageRemoved;
use CultuurNet\UDB3\Place\Events\ImageUpdated;
use CultuurNet\UDB3\Place\Events\LabelsImported;
use CultuurNet\UDB3\Place\Events\MainImageSelected;
use CultuurNet\UDB3\Place\Events\LabelAdded;
use CultuurNet\UDB3\Place\Events\LabelRemoved;
use CultuurNet\UDB3\Place\Events\MajorInfoUpdated;
use CultuurNet\UDB3\Place\Events\MarkedAsDuplicate;
use CultuurNet\UDB3\Place\Events\MarkedAsCanonical;
use CultuurNet\UDB3\Place\Events\Moderation\Approved;
use CultuurNet\UDB3\Place\Events\Moderation\FlaggedAsDuplicate;
use CultuurNet\UDB3\Place\Events\Moderation\FlaggedAsInappropriate;
use CultuurNet\UDB3\Place\Events\Moderation\Published;
use CultuurNet\UDB3\Place\Events\Moderation\Rejected;
use CultuurNet\UDB3\Place\Events\OrganizerDeleted;
use CultuurNet\UDB3\Place\Events\OrganizerUpdated;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceDeleted;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceUpdatedFromUDB2;
use CultuurNet\UDB3\Place\Events\PriceInfoUpdated;
use CultuurNet\UDB3\Place\Events\ThemeUpdated;
use CultuurNet\UDB3\Place\Events\TitleTranslated;
use CultuurNet\UDB3\Place\Events\TitleUpdated;
use CultuurNet\UDB3\Place\Events\TypeUpdated;
use CultuurNet\UDB3\Place\Events\TypicalAgeRangeDeleted;
use CultuurNet\UDB3\Place\Events\TypicalAgeRangeUpdated;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;
use DateTimeImmutable;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class Place extends Offer implements UpdateableWithCdbXmlInterface
{
    /**
     * @var string
     */
    private $placeId;

    /**
     * @var Address[]
     */
    private $addresses;

    /**
     * @var boolean
     */
    private $isDuplicate = false;

    /**
     * @var string[]
     */
    private $duplicates = [];

    /**
     * @var string|null
     */
    private $canonicalPlaceId;

    public function __construct()
    {
        parent::__construct();

        $this->addresses = [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAggregateRootId()
    {
        return $this->placeId;
    }

    /**
     * @todo Rename this method to create() after moving this part of the codebase to udb3-silex
     */
    public static function createPlace(
        string $id,
        Language $mainLanguage,
        Title $title,
        EventType $eventType,
        Address $address,
        CalendarInterface $calendar,
        Theme $theme = null,
        DateTimeImmutable $publicationDate = null
    ): self {
        $place = new self();
        $place->apply(new PlaceCreated(
            $id,
            $mainLanguage,
            $title,
            $eventType,
            $address,
            $calendar,
            $theme,
            $publicationDate
        ));

        return $place;
    }

    protected function applyPlaceCreated(PlaceCreated $placeCreated): void
    {
        $this->mainLanguage = $placeCreated->getMainLanguage();
        $this->titles[$this->mainLanguage->getCode()] = $placeCreated->getTitle();
        $this->calendar = $placeCreated->getCalendar();
        $this->contactPoint = new ContactPoint();
        $this->bookingInfo = new BookingInfo();
        $this->typeId = $placeCreated->getEventType()->getId();
        $this->themeId = $placeCreated->getTheme() ? $placeCreated->getTheme()->getId() : null;
        $this->addresses[$this->mainLanguage->getCode()] = $placeCreated->getAddress();
        $this->placeId = $placeCreated->getPlaceId();
        $this->workflowStatus = WorkflowStatus::DRAFT();
    }

    public function updateMajorInfo(
        Title $title,
        EventType $eventType,
        Address $address,
        CalendarInterface $calendar,
        Theme $theme = null
    ): void {
        $this->apply(
            new MajorInfoUpdated(
                $this->placeId,
                $title,
                $eventType,
                $address,
                $calendar,
                $theme
            )
        );
    }

    protected function applyMajorInfoUpdated(MajorInfoUpdated $majorInfoUpdated): void
    {
        $this->addresses[$this->mainLanguage->getCode()] = $majorInfoUpdated->getAddress();
    }

    public function updateAddress(Address $address, Language $language): void
    {
        if ($language->getCode() === $this->mainLanguage->getCode()) {
            $event = new AddressUpdated($this->placeId, $address);
        } else {
            $event = new AddressTranslated($this->placeId, $address, $language);
        }

        if ($this->allowAddressUpdate($address, $language)) {
            $this->apply($event);
        }
    }

    protected function applyAddressUpdated(AddressUpdated $addressUpdated): void
    {
        $this->addresses[$this->mainLanguage->getCode()] = $addressUpdated->getAddress();
    }

    protected function applyAddressTranslated(AddressTranslated $addressTranslated): void
    {
        $this->addresses[$addressTranslated->getLanguage()->getCode()] = $addressTranslated->getAddress();
    }

    public function markAsDuplicateOf(string $placeIdOfCanonical): void
    {
        if ($this->isDeleted) {
            throw CannotMarkPlaceAsDuplicate::becauseItIsDeleted($this->placeId);
        }

        if ($this->isDuplicate) {
            throw CannotMarkPlaceAsDuplicate::becauseItIsAlreadyADuplicate($this->placeId);
        }

        $this->apply(new MarkedAsDuplicate($this->placeId, $placeIdOfCanonical));
    }

    public function markAsCanonicalFor(string $placeIdOfDuplicate, array $duplicatesOfDuplicate = []): void
    {
        if ($this->isDeleted) {
            throw CannotMarkPlaceAsCanonical::becauseItIsDeleted($this->placeId);
        }

        if ($this->isDuplicate) {
            throw CannotMarkPlaceAsCanonical::becauseItIsAlreadyADuplicate($this->placeId);
        }

        $this->apply(new MarkedAsCanonical($this->placeId, $placeIdOfDuplicate, $duplicatesOfDuplicate));
    }

    /**
     * @return string[]
     */
    public function getDuplicates(): array
    {
        return $this->duplicates;
    }

    private function allowAddressUpdate(Address $address, Language $language): bool
    {
        // No current address in the provided language so update with new address is allowed.
        if (!isset($this->addresses[$language->getCode()])) {
            return true;
        }

        // The current address in de the provided language is different then the new address, so update allowed.
        if (!$this->addresses[$language->getCode()]->sameAs($address)) {
            return true;
        }

        return false;
    }

    public static function importFromUDB2Actor(
        string $actorId,
        string $cdbXml,
        string $cdbXmlNamespaceUri
    ): self {
        $place = new static();
        $place->apply(
            new PlaceImportedFromUDB2(
                $actorId,
                $cdbXml,
                $cdbXmlNamespaceUri
            )
        );

        return $place;
    }

    protected function applyPlaceImportedFromUDB2(PlaceImportedFromUDB2 $placeImported): void
    {
        $this->placeId = $placeImported->getActorId();

        // When importing from UDB2 the default main language is always 'nl'.
        $this->mainLanguage = new Language('nl');

        $udb2Actor = ActorItemFactory::createActorFromCdbXml(
            $placeImported->getCdbXmlNamespaceUri(),
            $placeImported->getCdbXml()
        );

        // Just clear the facilities.
        $this->facilities = [];

        // Just clear the contact point.
        $this->contactPoint = null;

        // Just clear the calendar.
        $this->calendar = null;

        // Note: an actor has no typical age range so after it can't be changed
        // by an UDB2 update. Nothing has to be done.

        // Just clear the booking info.
        $this->bookingInfo = null;

        // Just clear the price info.
        $this->priceInfo = null;

        $this->importWorkflowStatus($udb2Actor);
        $this->labels = LabelCollection::fromKeywords($udb2Actor->getKeywords(true));
    }

    protected function applyPlaceUpdatedFromUDB2(PlaceUpdatedFromUDB2 $placeUpdatedFromUDB2): void
    {
        // Note: when updating from UDB2 never change the main language.

        $udb2Actor = ActorItemFactory::createActorFromCdbXml(
            $placeUpdatedFromUDB2->getCdbXmlNamespaceUri(),
            $placeUpdatedFromUDB2->getCdbXml()
        );

        // Just clear the facilities.
        $this->facilities = [];

        // Just clear the contact point.
        $this->contactPoint = null;

        // Just clear the calendar.
        $this->calendar = null;

        // Note: an actor has no typical age range so after it can't be changed
        // by an UDB2 update. Nothing has to be done.

        // Just clear the booking info.
        $this->bookingInfo = null;

        // Just clear the price info.
        $this->priceInfo = null;

        $this->importWorkflowStatus($udb2Actor);
        $this->labels = LabelCollection::fromKeywords($udb2Actor->getKeywords(true));

        unset($this->addresses[$this->mainLanguage->getCode()]);
    }

    protected function applyPlaceDeleted(PlaceDeleted $event): void
    {
        $this->isDeleted = true;
    }

    protected function applyMarkedAsDuplicate(MarkedAsDuplicate $event): void
    {
        $this->isDuplicate = true;
        $this->canonicalPlaceId = $event->getDuplicateOf();
    }

    protected function applyMarkedAsCanonical(MarkedAsCanonical $event): void
    {
        $this->duplicates[] = $event->getDuplicatedBy();
        foreach ($event->getDuplicatesOfDuplicate() as $duplicateOfDuplicate) {
            $this->duplicates[] = $duplicateOfDuplicate;
        }
    }

    public function getCanonicalPlaceId(): ?string
    {
        return $this->canonicalPlaceId;
    }

    /**
     * @inheritdoc
     */
    public function updateWithCdbXml($cdbXml, $cdbXmlNamespaceUri)
    {
        ActorItemFactory::createActorFromCdbXml($cdbXmlNamespaceUri, $cdbXml);

        $this->apply(
            new PlaceUpdatedFromUDB2(
                $this->placeId,
                $cdbXml,
                $cdbXmlNamespaceUri
            )
        );
    }

    /**
     * @param Label $label
     * @return LabelAdded
     */
    protected function createLabelAddedEvent(Label $label)
    {
        return new LabelAdded($this->placeId, $label);
    }

    /**
     * @param Label $label
     * @return LabelRemoved
     */
    protected function createLabelRemovedEvent(Label $label)
    {
        return new LabelRemoved($this->placeId, $label);
    }

    /**
     * @inheritdoc
     */
    protected function createLabelsImportedEvent(Labels $labels)
    {
        return new LabelsImported($this->placeId, $labels);
    }

    protected function createImageAddedEvent(Image $image)
    {
        return new ImageAdded($this->placeId, $image);
    }

    protected function createImageRemovedEvent(Image $image)
    {
        return new ImageRemoved($this->placeId, $image);
    }

    protected function createImageUpdatedEvent(
        UUID $mediaObjectId,
        StringLiteral $description,
        StringLiteral $copyrightHolder
    ) {
        return new ImageUpdated(
            $this->placeId,
            $mediaObjectId,
            $description,
            $copyrightHolder
        );
    }

    protected function createMainImageSelectedEvent(Image $image)
    {
        return new MainImageSelected($this->placeId, $image);
    }

    /**
     * @inheritdoc
     */
    protected function createTitleTranslatedEvent(Language $language, Title $title)
    {
        return new TitleTranslated($this->placeId, $language, $title);
    }

    /**
     * @param Title $title
     * @return TitleUpdated
     */
    protected function createTitleUpdatedEvent(Title $title)
    {
        return new TitleUpdated($this->placeId, $title);
    }

    /**
     * @inheritdoc
     */
    protected function createDescriptionTranslatedEvent(Language $language, Description $description)
    {
        return new DescriptionTranslated($this->placeId, $language, $description);
    }

    /**
     * @inheritdoc
     */
    protected function createDescriptionUpdatedEvent(Description $description)
    {
        return new DescriptionUpdated($this->placeId, $description);
    }

    /**
     * @inheritdoc
     */
    protected function createCalendarUpdatedEvent(Calendar $calendar)
    {
        return new CalendarUpdated($this->placeId, $calendar);
    }

    /**
     * @param AgeRange $typicalAgeRange
     * @return TypicalAgeRangeUpdated
     */
    protected function createTypicalAgeRangeUpdatedEvent($typicalAgeRange)
    {
        return new TypicalAgeRangeUpdated($this->placeId, $typicalAgeRange);
    }

    /**
     * @return TypicalAgeRangeDeleted
     */
    protected function createTypicalAgeRangeDeletedEvent()
    {
        return new TypicalAgeRangeDeleted($this->placeId);
    }

    /**
     * @param string $organizerId
     * @return OrganizerUpdated
     */
    protected function createOrganizerUpdatedEvent($organizerId)
    {
        return new OrganizerUpdated($this->placeId, $organizerId);
    }

    /**
     * @param string $organizerId
     * @return OrganizerDeleted
     */
    protected function createOrganizerDeletedEvent($organizerId)
    {
        return new OrganizerDeleted($this->placeId, $organizerId);
    }

    /**
     * @param ContactPoint $contactPoint
     * @return ContactPointUpdated
     */
    protected function createContactPointUpdatedEvent(ContactPoint $contactPoint)
    {
        return new ContactPointUpdated($this->placeId, $contactPoint);
    }

    /**
     * @inheritdoc
     */
    protected function createGeoCoordinatesUpdatedEvent(Coordinates $coordinates)
    {
        return new GeoCoordinatesUpdated($this->placeId, $coordinates);
    }

    /**
     * @param BookingInfo $bookingInfo
     * @return BookingInfoUpdated
     */
    protected function createBookingInfoUpdatedEvent(BookingInfo $bookingInfo)
    {
        return new BookingInfoUpdated($this->placeId, $bookingInfo);
    }

    /**
     * @param PriceInfo $priceInfo
     * @return PriceInfoUpdated
     */
    protected function createPriceInfoUpdatedEvent(PriceInfo $priceInfo)
    {
        return new PriceInfoUpdated($this->placeId, $priceInfo);
    }

    /**
     * @return PlaceDeleted
     */
    protected function createOfferDeletedEvent()
    {
        return new PlaceDeleted($this->placeId);
    }

    /**
     * @inheritDoc
     */
    protected function createPublishedEvent(\DateTimeInterface $publicationDate)
    {
        return new Published($this->placeId, $publicationDate);
    }

    /**
     * @inheritDoc
     */
    protected function createApprovedEvent()
    {
        return new Approved($this->placeId);
    }

    /**
     * @inheritDoc
     */
    protected function createRejectedEvent(StringLiteral $reason)
    {
        return new Rejected($this->placeId, $reason);
    }

    /**
     * @inheritDoc
     */
    protected function createFlaggedAsDuplicate()
    {
        return new FlaggedAsDuplicate($this->placeId);
    }

    /**
     * @inheritDoc
     */
    protected function createFlaggedAsInappropriate()
    {
        return new FlaggedAsInappropriate($this->placeId);
    }

    /**
     * @inheritDoc
     * @return ImagesImportedFromUDB2
     */
    protected function createImagesImportedFromUDB2(ImageCollection $images)
    {
        return new ImagesImportedFromUDB2($this->placeId, $images);
    }

    /**
     * @inheritDoc
     * @return ImagesUpdatedFromUDB2
     */
    protected function createImagesUpdatedFromUDB2(ImageCollection $images)
    {
        return new ImagesUpdatedFromUDB2($this->placeId, $images);
    }

    protected function createTypeUpdatedEvent(EventType $type)
    {
        return new TypeUpdated($this->placeId, $type);
    }

    protected function createThemeUpdatedEvent(Theme $theme)
    {
        return new ThemeUpdated($this->placeId, $theme);
    }

    /**
     * @inheritdoc
     */
    protected function createFacilitiesUpdatedEvent(array $facilities)
    {
        return new FacilitiesUpdated($this->placeId, $facilities);
    }
}
