<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Item;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Media\Properties\Description as ImageDescription;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Audience\AgeRange;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Events\AbstractOwnerChanged;
use CultuurNet\UDB3\Offer\Item\Events\AvailableFromUpdated;
use CultuurNet\UDB3\Offer\Item\Events\BookingInfoUpdated;
use CultuurNet\UDB3\Offer\Item\Events\CalendarUpdated;
use CultuurNet\UDB3\Offer\Item\Events\ContactPointUpdated;
use CultuurNet\UDB3\Offer\Item\Events\DescriptionDeleted;
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
use CultuurNet\UDB3\Offer\Item\Events\OwnerChanged;
use CultuurNet\UDB3\Offer\Item\Events\PriceInfoUpdated;
use CultuurNet\UDB3\Offer\Item\Events\TitleTranslated;
use CultuurNet\UDB3\Offer\Item\Events\TitleUpdated;
use CultuurNet\UDB3\Offer\Item\Events\TypeUpdated;
use CultuurNet\UDB3\Offer\Item\Events\TypicalAgeRangeDeleted;
use CultuurNet\UDB3\Offer\Item\Events\TypicalAgeRangeUpdated;
use CultuurNet\UDB3\Offer\Item\Events\VideoAdded;
use CultuurNet\UDB3\Offer\Item\Events\VideoDeleted;
use CultuurNet\UDB3\Offer\Item\Events\VideoUpdated;
use CultuurNet\UDB3\Offer\Offer;
use CultuurNet\UDB3\Offer\Item\Events\ImageAdded;
use CultuurNet\UDB3\Offer\Item\Events\ImageRemoved;
use CultuurNet\UDB3\Offer\Item\Events\ImageUpdated;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use DateTimeInterface;
use RuntimeException;

/**
 * @deprecated
 *   Use a real Offer implementation in tests instead.
 */
final class Item extends Offer
{
    protected string $id;

    public static function getOfferType(): OfferType
    {
        throw new RuntimeException('Item is a deprecated Offer implementation for legacy unit tests and has no real offer type.');
    }

    protected function applyItemCreated(ItemCreated $created): void
    {
        $this->id = $created->getItemId();
        $this->mainLanguage = $created->getMainLanguage();
        $this->workflowStatus = WorkflowStatus::DRAFT();
    }

    protected function applyItemDeleted(ItemDeleted $event): void
    {
        $this->workflowStatus = WorkflowStatus::DELETED();
    }

    protected function createOwnerChangedEvent(string $newOwnerId): AbstractOwnerChanged
    {
        return new OwnerChanged($this->id, $newOwnerId);
    }

    protected function createLabelAddedEvent(string $labelName, bool $isVisible): LabelAdded
    {
        return new LabelAdded($this->id, $labelName, $isVisible);
    }

    protected function createLabelRemovedEvent(string $labelName): LabelRemoved
    {
        return new LabelRemoved($this->id, $labelName);
    }

    protected function createLabelsImportedEvent(Labels $labels): LabelsImported
    {
        return new LabelsImported($this->id, $labels->getVisibleLabels()->toArrayOfStringNames(), $labels->getHiddenLabels()->toArrayOfStringNames());
    }

    protected function createImageAddedEvent(Image $image): ImageAdded
    {
        return new ImageAdded($this->id, $image);
    }

    protected function createImageRemovedEvent(Image $image): ImageRemoved
    {
        return new ImageRemoved($this->id, $image);
    }

    protected function createImageUpdatedEvent(
        UUID $mediaObjectId,
        ImageDescription $description,
        CopyrightHolder $copyrightHolder,
        ?string $language = null
    ): ImageUpdated {
        return new ImageUpdated(
            $this->id,
            $mediaObjectId->toString(),
            $description->toString(),
            $copyrightHolder->toString(),
            $language
        );
    }

    protected function createMainImageSelectedEvent(Image $image): MainImageSelected
    {
        return new MainImageSelected($this->id, $image);
    }

    protected function createVideoAddedEvent(Video $video): VideoAdded
    {
        return new VideoAdded($this->id, $video);
    }

    protected function createVideoDeletedEvent(string $videoId): VideoDeleted
    {
        return new VideoDeleted($this->id, $videoId);
    }

    protected function createVideoUpdatedEvent(Video $video): VideoUpdated
    {
        return new VideoUpdated($this->id, $video);
    }

    public function getAggregateRootId(): string
    {
        return $this->id;
    }

    protected function createTitleTranslatedEvent(Language $language, Title $title): TitleTranslated
    {
        return new TitleTranslated($this->id, $language, $title->toString());
    }

    protected function createTitleUpdatedEvent(Title $title): TitleUpdated
    {
        return new TitleUpdated($this->id, $title->toString());
    }

    protected function createDescriptionTranslatedEvent(Language $language, Description $description): DescriptionTranslated
    {
        return new DescriptionTranslated($this->id, $language, $description);
    }

    protected function createDescriptionUpdatedEvent(Description $description): DescriptionUpdated
    {
        return new DescriptionUpdated($this->id, $description);
    }

    protected function createDescriptionDeletedEvent(Language $language): DescriptionDeleted
    {
        return new DescriptionDeleted($this->id, $language);
    }

    protected function createCalendarUpdatedEvent(Calendar $calendar): CalendarUpdated
    {
        return new CalendarUpdated($this->id, $calendar);
    }

    protected function createTypicalAgeRangeUpdatedEvent(AgeRange $typicalAgeRange): TypicalAgeRangeUpdated
    {
        return new TypicalAgeRangeUpdated($this->id, $typicalAgeRange);
    }

    protected function createTypicalAgeRangeDeletedEvent(): TypicalAgeRangeDeleted
    {
        return new TypicalAgeRangeDeleted($this->id);
    }

    protected function createOrganizerUpdatedEvent(string $organizerId): OrganizerUpdated
    {
        return new OrganizerUpdated($this->id, $organizerId);
    }

    protected function createOrganizerDeletedEvent(string $organizerId): OrganizerDeleted
    {
        return new OrganizerDeleted($this->id, $organizerId);
    }

    protected function createContactPointUpdatedEvent(ContactPoint $contactPoint): ContactPointUpdated
    {
        return new ContactPointUpdated($this->id, $contactPoint);
    }

    protected function createGeoCoordinatesUpdatedEvent(Coordinates $coordinates): GeoCoordinatesUpdated
    {
        return new GeoCoordinatesUpdated($this->id, $coordinates);
    }

    protected function createBookingInfoUpdatedEvent(BookingInfo $bookingInfo): BookingInfoUpdated
    {
        return new BookingInfoUpdated($this->id, $bookingInfo);
    }

    protected function createPriceInfoUpdatedEvent(PriceInfo $priceInfo): PriceInfoUpdated
    {
        return new PriceInfoUpdated($this->id, $priceInfo);
    }

    protected function createOfferDeletedEvent(): ItemDeleted
    {
        return new ItemDeleted($this->id);
    }

    protected function createAvailableFromUpdatedEvent(DateTimeInterface $availableFrom): AvailableFromUpdated
    {
        return new AvailableFromUpdated($this->id, $availableFrom);
    }

    protected function createPublishedEvent(\DateTimeInterface $publicationDate): Published
    {
        return new Published($this->id, $publicationDate);
    }

    protected function createApprovedEvent(): Approved
    {
        return new Approved($this->id);
    }

    protected function createImagesImportedFromUDB2(ImageCollection $images): ImagesImportedFromUDB2
    {
        return new ImagesImportedFromUDB2($this->id, $images);
    }

    protected function createImagesUpdatedFromUDB2(ImageCollection $images): ImagesUpdatedFromUDB2
    {
        return new ImagesUpdatedFromUDB2($this->id, $images);
    }

    protected function createRejectedEvent(string $reason): Rejected
    {
        return new Rejected($this->id, $reason);
    }

    protected function createFlaggedAsDuplicate(): FlaggedAsDuplicate
    {
        return new FlaggedAsDuplicate($this->id);
    }

    protected function createFlaggedAsInappropriate(): FlaggedAsInappropriate
    {
        return new FlaggedAsInappropriate($this->id);
    }

    protected function createTypeUpdatedEvent(Category $type): TypeUpdated
    {
        return new TypeUpdated($this->id, $type);
    }

    protected function createFacilitiesUpdatedEvent(array $facilities): FacilitiesUpdated
    {
        return new FacilitiesUpdated($this->id, $facilities);
    }
}
