<?php

namespace CultuurNet\UDB3\Offer\Item;

use CultuurNet\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\AgeRange;
use CultuurNet\UDB3\Offer\Item\Events\BookingInfoUpdated;
use CultuurNet\UDB3\Offer\Item\Events\CalendarUpdated;
use CultuurNet\UDB3\Offer\Item\Events\ContactPointUpdated;
use CultuurNet\UDB3\Offer\Item\Events\DescriptionTranslated;
use CultuurNet\UDB3\Offer\Item\Events\DescriptionUpdated;
use CultuurNet\UDB3\Offer\Item\Events\FacilitiesUpdated;
use CultuurNet\UDB3\Offer\Item\Events\GeoCoordinatesUpdated;
use CultuurNet\UDB3\Offer\Item\Events\Image\ImagesImportedFromUDB2;
use CultuurNet\UDB3\Offer\Item\Events\Image\ImagesUpdatedFromUDB2;
use CultuurNet\UDB3\Offer\Item\Events\ItemCreated;
use CultuurNet\UDB3\Offer\Item\Events\ItemDeleted;
use CultuurNet\UDB3\Offer\Item\Events\LabelAdded;
use CultuurNet\UDB3\Offer\Item\Events\LabelRemoved;
use CultuurNet\UDB3\Offer\Item\Events\LabelsImported;
use CultuurNet\UDB3\Offer\Item\Events\MainImageSelected;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\Approved;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\FlaggedAsDuplicate;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\FlaggedAsInappropriate;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\Published;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\Rejected;
use CultuurNet\UDB3\Offer\Item\Events\OrganizerDeleted;
use CultuurNet\UDB3\Offer\Item\Events\OrganizerUpdated;
use CultuurNet\UDB3\Offer\Item\Events\PriceInfoUpdated;
use CultuurNet\UDB3\Offer\Item\Events\ThemeUpdated;
use CultuurNet\UDB3\Offer\Item\Events\TitleTranslated;
use CultuurNet\UDB3\Offer\Item\Events\TitleUpdated;
use CultuurNet\UDB3\Offer\Item\Events\TypeUpdated;
use CultuurNet\UDB3\Offer\Item\Events\TypicalAgeRangeDeleted;
use CultuurNet\UDB3\Offer\Item\Events\TypicalAgeRangeUpdated;
use CultuurNet\UDB3\Offer\Offer;
use CultuurNet\UDB3\Offer\Item\Events\ImageAdded;
use CultuurNet\UDB3\Offer\Item\Events\ImageRemoved;
use CultuurNet\UDB3\Offer\Item\Events\ImageUpdated;
use CultuurNet\UDB3\Offer\WorkflowStatus;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class Item extends Offer
{
    /**
     * @var mixed
     */
    protected $id;

    /**
     * @param ItemCreated $created
     */
    protected function applyItemCreated(ItemCreated $created)
    {
        $this->id = $created->getItemId();
        $this->mainLanguage = $created->getMainLanguage();
        $this->workflowStatus = WorkflowStatus::DRAFT();
    }

    protected function applyItemDeleted(ItemDeleted $event): void
    {
        $this->isDeleted = true;
    }

    /**
     * @param Label $label
     * @return LabelAdded
     */
    protected function createLabelAddedEvent(Label $label)
    {
        return new LabelAdded($this->id, $label);
    }

    /**
     * @param Label $label
     * @return LabelRemoved
     */
    protected function createLabelRemovedEvent(Label $label)
    {
        return new LabelRemoved($this->id, $label);
    }

    /**
     * @inheritdoc
     */
    protected function createLabelsImportedEvent(Labels $labels)
    {
        return new LabelsImported($this->id, $labels);
    }

    protected function createImageAddedEvent(Image $image)
    {
        return new ImageAdded($this->id, $image);
    }

    protected function createImageRemovedEvent(Image $image)
    {
        return new ImageRemoved($this->id, $image);
    }

    protected function createImageUpdatedEvent(
        UUID $mediaObjectId,
        StringLiteral $description,
        StringLiteral $copyrightHolder
    ) {
        return new ImageUpdated(
            $this->id,
            $mediaObjectId,
            $description,
            $copyrightHolder
        );
    }

    protected function createMainImageSelectedEvent(Image $image)
    {
        return new MainImageSelected($this->id, $image);
    }

    /**
     * @return mixed
     */
    public function getAggregateRootId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    protected function createTitleTranslatedEvent(Language $language, Title $title)
    {
        return new TitleTranslated($this->id, $language, $title);
    }

    /**
     * @param Title $title
     * @return TitleUpdated
     */
    protected function createTitleUpdatedEvent(Title $title)
    {
        return new TitleUpdated($this->id, $title);
    }

    /**
     * @inheritdoc
     */
    protected function createDescriptionTranslatedEvent(Language $language, Description $description)
    {
        return new DescriptionTranslated($this->id, $language, $description);
    }

    /**
     * @inheritdoc
     */
    protected function createDescriptionUpdatedEvent(Description $description)
    {
        return new DescriptionUpdated($this->id, $description);
    }

    /**
     * @inheritdoc
     */
    protected function createCalendarUpdatedEvent(Calendar $calendar)
    {
        return new CalendarUpdated($this->id, $calendar);
    }

    /**
     * @param AgeRange $typicalAgeRange
     * @return TypicalAgeRangeUpdated
     */
    protected function createTypicalAgeRangeUpdatedEvent($typicalAgeRange)
    {
        return new TypicalAgeRangeUpdated($this->id, $typicalAgeRange);
    }

    /**
     * @return TypicalAgeRangeDeleted
     */
    protected function createTypicalAgeRangeDeletedEvent()
    {
        return new TypicalAgeRangeDeleted($this->id);
    }

    /**
     * @param string $organizerId
     * @return OrganizerUpdated
     */
    protected function createOrganizerUpdatedEvent($organizerId)
    {
        return new OrganizerUpdated($this->id, $organizerId);
    }

    /**
     * @param string $organizerId
     * @return OrganizerDeleted
     */
    protected function createOrganizerDeletedEvent($organizerId)
    {
        return new OrganizerDeleted($this->id, $organizerId);
    }

    /**
     * @param ContactPoint $contactPoint
     * @return ContactPointUpdated
     */
    protected function createContactPointUpdatedEvent(ContactPoint $contactPoint)
    {
        return new ContactPointUpdated($this->id, $contactPoint);
    }

    /**
     * @inheritdoc
     */
    protected function createGeoCoordinatesUpdatedEvent(Coordinates $coordinates)
    {
        return new GeoCoordinatesUpdated($this->id, $coordinates);
    }

    /**
     * @param BookingInfo $bookingInfo
     * @return BookingInfoUpdated
     */
    protected function createBookingInfoUpdatedEvent(BookingInfo $bookingInfo)
    {
        return new BookingInfoUpdated($this->id, $bookingInfo);
    }

    /**
     * @param PriceInfo $priceInfo
     * @return PriceInfoUpdated
     */
    protected function createPriceInfoUpdatedEvent(PriceInfo $priceInfo)
    {
        return new PriceInfoUpdated($this->id, $priceInfo);
    }

    /**
     * @inheritdoc
     */
    protected function createOfferDeletedEvent()
    {
        return new ItemDeleted($this->id);
    }

    /**
     * @inheritdoc
     */
    protected function createPublishedEvent(\DateTimeInterface $publicationDate)
    {
        return new Published($this->id, $publicationDate);
    }

    /**
     * @inheritdoc
     */
    protected function createApprovedEvent()
    {
        return new Approved($this->id);
    }

    /**
     * @inheritDoc
     * @return ImagesImportedFromUDB2
     */
    protected function createImagesImportedFromUDB2(ImageCollection $images)
    {
        return new ImagesImportedFromUDB2($this->id, $images);
    }

    /**
     * @inheritDoc
     * @return ImagesUpdatedFromUDB2
     */
    protected function createImagesUpdatedFromUDB2(ImageCollection $images)
    {
        return new ImagesUpdatedFromUDB2($this->id, $images);
    }

    /**
     * @inheritdoc
     */
    protected function createRejectedEvent(StringLiteral $reason)
    {
        return new Rejected($this->id, $reason);
    }

    /**
     * @inheritDoc
     */
    protected function createFlaggedAsDuplicate()
    {
        return new FlaggedAsDuplicate($this->id);
    }

    /**
     * @inheritDoc
     */
    protected function createFlaggedAsInappropriate()
    {
        return new FlaggedAsInappropriate($this->id);
    }

    protected function createTypeUpdatedEvent(EventType $type)
    {
        return new TypeUpdated($this->id, $type);
    }

    protected function createThemeUpdatedEvent(Theme $theme)
    {
        return new ThemeUpdated($this->id, $theme);
    }

    /**
     * @inheritdoc
     */
    protected function createFacilitiesUpdatedEvent(array $facilities)
    {
        return new FacilitiesUpdated($this->id, $facilities);
    }
}
