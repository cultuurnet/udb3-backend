<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Calendar\CalendarFactory;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\ContactPoint as LegacyContactPoint;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Media\Properties\Description as ImageDescription;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Offer\AgeRange;
use CultuurNet\UDB3\Offer\Events\AbstractOwnerChanged;
use CultuurNet\UDB3\Offer\Offer;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Offer\LabelsArray;
use CultuurNet\UDB3\Place\Events\AddressTranslated;
use CultuurNet\UDB3\Place\Events\AddressUpdated;
use CultuurNet\UDB3\Place\Events\AvailableFromUpdated;
use CultuurNet\UDB3\Place\Events\BookingInfoUpdated;
use CultuurNet\UDB3\Place\Events\CalendarUpdated;
use CultuurNet\UDB3\Place\Events\ContactPointUpdated;
use CultuurNet\UDB3\Place\Events\DescriptionDeleted;
use CultuurNet\UDB3\Place\Events\DescriptionTranslated;
use CultuurNet\UDB3\Place\Events\DescriptionUpdated;
use CultuurNet\UDB3\Place\Events\FacilitiesUpdated;
use CultuurNet\UDB3\Place\Events\GeoCoordinatesUpdated;
use CultuurNet\UDB3\Place\Events\Image\ImagesImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\Image\ImagesUpdatedFromUDB2;
use CultuurNet\UDB3\Place\Events\ImageAdded;
use CultuurNet\UDB3\Place\Events\ImageRemoved;
use CultuurNet\UDB3\Place\Events\ImageUpdated;
use CultuurNet\UDB3\Place\Events\LabelAdded;
use CultuurNet\UDB3\Place\Events\LabelRemoved;
use CultuurNet\UDB3\Place\Events\LabelsImported;
use CultuurNet\UDB3\Place\Events\MainImageSelected;
use CultuurNet\UDB3\Place\Events\MajorInfoUpdated;
use CultuurNet\UDB3\Place\Events\Moderation\Approved;
use CultuurNet\UDB3\Place\Events\Moderation\FlaggedAsDuplicate;
use CultuurNet\UDB3\Place\Events\Moderation\FlaggedAsInappropriate;
use CultuurNet\UDB3\Place\Events\Moderation\Published;
use CultuurNet\UDB3\Place\Events\Moderation\Rejected;
use CultuurNet\UDB3\Place\Events\OrganizerDeleted;
use CultuurNet\UDB3\Place\Events\OrganizerUpdated;
use CultuurNet\UDB3\Place\Events\OwnerChanged;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceDeleted;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceUpdatedFromUDB2;
use CultuurNet\UDB3\Place\Events\PriceInfoUpdated;
use CultuurNet\UDB3\Place\Events\TitleTranslated;
use CultuurNet\UDB3\Place\Events\TitleUpdated;
use CultuurNet\UDB3\Place\Events\TypeUpdated;
use CultuurNet\UDB3\Place\Events\TypicalAgeRangeDeleted;
use CultuurNet\UDB3\Place\Events\TypicalAgeRangeUpdated;
use CultuurNet\UDB3\Place\Events\VideoAdded;
use CultuurNet\UDB3\Place\Events\VideoDeleted;
use CultuurNet\UDB3\Place\Events\VideoUpdated;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use DateTimeImmutable;
use DateTimeInterface;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language as Udb3Language;

class Place extends Offer
{
    private string $placeId;

    /**
     * @var Address[]
     */
    private array $addresses;

    /**
     * @var string[]
     */
    private array $duplicates = [];

    private ?string $canonicalPlaceId = null;

    public function __construct()
    {
        parent::__construct();

        $this->addresses = [];
    }

    public static function getOfferType(): OfferType
    {
        return OfferType::place();
    }

    public function getAggregateRootId(): string
    {
        return $this->placeId;
    }

    public static function create(
        string $id,
        Language $mainLanguage,
        Title $title,
        EventType $eventType,
        Address $address,
        Calendar $calendar,
        DateTimeImmutable $publicationDate = null
    ): self {
        $place = new self();
        $place->apply(new PlaceCreated(
            $id,
            $mainLanguage,
            $title->toString(),
            $eventType,
            $address,
            $calendar,
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
        $this->addresses[$this->mainLanguage->getCode()] = $placeCreated->getAddress();
        $this->placeId = $placeCreated->getPlaceId();
        $this->workflowStatus = WorkflowStatus::DRAFT();
    }

    public function updateMajorInfo(
        Title $title,
        EventType $eventType,
        Address $address,
        Calendar $calendar
    ): void {
        $this->apply(
            new MajorInfoUpdated(
                $this->placeId,
                $title->toString(),
                $eventType,
                $address,
                $calendar
            )
        );
    }

    protected function applyMajorInfoUpdated(MajorInfoUpdated $majorInfoUpdated): void
    {
        $this->addresses[$this->mainLanguage->getCode()] = $majorInfoUpdated->getAddress();
        $this->calendar = $majorInfoUpdated->getCalendar();
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
        $place = new self();
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

        // Just reset the facilities.
        $this->facilities = null;

        // Just clear the contact point.
        $this->contactPoint = null;

        // Correctly set the Calendar
        // We need this for future Status updates
        $calendarFactory = new CalendarFactory();
        $this->calendar = $calendarFactory->createFromWeekScheme($udb2Actor->getWeekScheme());

        // Note: an actor has no typical age range so after it can't be changed
        // by an UDB2 update. Nothing has to be done.

        // Just clear the booking info.
        $this->bookingInfo = null;

        // Just clear the price info.
        $this->priceInfo = null;

        $this->importWorkflowStatus($udb2Actor);

        $this->labels = LabelsArray::createFromKeywords($udb2Actor->getKeywords(true));
    }

    protected function applyPlaceUpdatedFromUDB2(PlaceUpdatedFromUDB2 $placeUpdatedFromUDB2): void
    {
        // Note: when updating from UDB2 never change the main language.

        $udb2Actor = ActorItemFactory::createActorFromCdbXml(
            $placeUpdatedFromUDB2->getCdbXmlNamespaceUri(),
            $placeUpdatedFromUDB2->getCdbXml()
        );

        // Just reset the facilities.
        $this->facilities = null;

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

        $this->labels = LabelsArray::createFromKeywords($udb2Actor->getKeywords(true));

        unset($this->addresses[$this->mainLanguage->getCode()]);
    }

    protected function applyPlaceDeleted(PlaceDeleted $event): void
    {
        $this->workflowStatus = WorkflowStatus::DELETED();
    }

    public function getCanonicalPlaceId(): ?string
    {
        return $this->canonicalPlaceId;
    }

    public function updateWithCdbXml(string $cdbXml, string $cdbXmlNamespaceUri): void
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

    protected function createOwnerChangedEvent(string $newOwnerId): AbstractOwnerChanged
    {
        return new OwnerChanged($this->placeId, $newOwnerId);
    }

    protected function createLabelAddedEvent(string $labelName, bool $isVisible): LabelAdded
    {
        return new LabelAdded($this->placeId, $labelName, $isVisible);
    }

    protected function createLabelRemovedEvent(string $labelName): LabelRemoved
    {
        return new LabelRemoved($this->placeId, $labelName);
    }

    protected function createLabelsImportedEvent(Labels $labels): LabelsImported
    {
        return new LabelsImported(
            $this->placeId,
            $labels->getVisibleLabels()->toArrayOfStringNames(),
            $labels->getHiddenLabels()->toArrayOfStringNames()
        );
    }

    protected function createImageAddedEvent(Image $image): ImageAdded
    {
        return new ImageAdded($this->placeId, $image);
    }

    protected function createImageRemovedEvent(Image $image): ImageRemoved
    {
        return new ImageRemoved($this->placeId, $image);
    }

    protected function createImageUpdatedEvent(
        UUID $mediaObjectId,
        ImageDescription $description,
        CopyrightHolder $copyrightHolder,
        ?string $language = null
    ): ImageUpdated {
        return new ImageUpdated(
            $this->placeId,
            $mediaObjectId->toString(),
            $description->toString(),
            $copyrightHolder->toString(),
            $language
        );
    }

    protected function createMainImageSelectedEvent(Image $image): MainImageSelected
    {
        return new MainImageSelected($this->placeId, $image);
    }

    protected function createVideoAddedEvent(Video $video): VideoAdded
    {
        return new VideoAdded($this->placeId, $video);
    }

    protected function createVideoDeletedEvent(string $videoId): VideoDeleted
    {
        return new VideoDeleted($this->placeId, $videoId);
    }

    protected function createVideoUpdatedEvent(Video $video): VideoUpdated
    {
        return new VideoUpdated($this->placeId, $video);
    }

    protected function createTitleTranslatedEvent(Language $language, Title $title): TitleTranslated
    {
        return new TitleTranslated($this->placeId, $language, $title->toString());
    }

    protected function createTitleUpdatedEvent(Title $title): TitleUpdated
    {
        return new TitleUpdated($this->placeId, $title->toString());
    }

    protected function createDescriptionTranslatedEvent(Language $language, Description $description): DescriptionTranslated
    {
        return new DescriptionTranslated($this->placeId, $language, $description);
    }

    protected function createDescriptionUpdatedEvent(Description $description): DescriptionUpdated
    {
        return new DescriptionUpdated($this->placeId, $description);
    }

    protected function createDescriptionDeletedEvent(Udb3Language $language): DescriptionDeleted
    {
        return new DescriptionDeleted($this->placeId, $language);
    }

    protected function createCalendarUpdatedEvent(Calendar $calendar): CalendarUpdated
    {
        return new CalendarUpdated($this->placeId, $calendar);
    }

    protected function createTypicalAgeRangeUpdatedEvent(AgeRange $typicalAgeRange): TypicalAgeRangeUpdated
    {
        return new TypicalAgeRangeUpdated($this->placeId, $typicalAgeRange);
    }

    protected function createTypicalAgeRangeDeletedEvent(): TypicalAgeRangeDeleted
    {
        return new TypicalAgeRangeDeleted($this->placeId);
    }

    protected function createOrganizerUpdatedEvent(string $organizerId): OrganizerUpdated
    {
        return new OrganizerUpdated($this->placeId, $organizerId);
    }

    protected function createOrganizerDeletedEvent(string $organizerId): OrganizerDeleted
    {
        return new OrganizerDeleted($this->placeId, $organizerId);
    }

    protected function createContactPointUpdatedEvent(ContactPoint $contactPoint): ContactPointUpdated
    {
        return new ContactPointUpdated($this->placeId, LegacyContactPoint::fromUdb3ModelContactPoint($contactPoint));
    }

    protected function createGeoCoordinatesUpdatedEvent(Coordinates $coordinates): GeoCoordinatesUpdated
    {
        return new GeoCoordinatesUpdated($this->placeId, $coordinates);
    }

    protected function createBookingInfoUpdatedEvent(BookingInfo $bookingInfo): BookingInfoUpdated
    {
        return new BookingInfoUpdated($this->placeId, $bookingInfo);
    }

    protected function createPriceInfoUpdatedEvent(PriceInfo $priceInfo): PriceInfoUpdated
    {
        return new PriceInfoUpdated($this->placeId, $priceInfo);
    }

    protected function createOfferDeletedEvent(): PlaceDeleted
    {
        return new PlaceDeleted($this->placeId);
    }

    protected function createAvailableFromUpdatedEvent(DateTimeInterface $availableFrom): AvailableFromUpdated
    {
        return new AvailableFromUpdated($this->placeId, $availableFrom);
    }

    protected function createPublishedEvent(\DateTimeInterface $publicationDate): Published
    {
        return new Published($this->placeId, $publicationDate);
    }

    protected function createApprovedEvent(): Approved
    {
        return new Approved($this->placeId);
    }

    protected function createRejectedEvent(string $reason): Rejected
    {
        return new Rejected($this->placeId, $reason);
    }

    protected function createFlaggedAsDuplicate(): FlaggedAsDuplicate
    {
        return new FlaggedAsDuplicate($this->placeId);
    }

    protected function createFlaggedAsInappropriate(): FlaggedAsInappropriate
    {
        return new FlaggedAsInappropriate($this->placeId);
    }

    protected function createImagesImportedFromUDB2(ImageCollection $images): ImagesImportedFromUDB2
    {
        return new ImagesImportedFromUDB2($this->placeId, $images);
    }

    protected function createImagesUpdatedFromUDB2(ImageCollection $images): ImagesUpdatedFromUDB2
    {
        return new ImagesUpdatedFromUDB2($this->placeId, $images);
    }

    protected function createTypeUpdatedEvent(EventType $type): TypeUpdated
    {
        return new TypeUpdated($this->placeId, $type);
    }

    protected function createFacilitiesUpdatedEvent(array $facilities): FacilitiesUpdated
    {
        return new FacilitiesUpdated($this->placeId, $facilities);
    }
}
