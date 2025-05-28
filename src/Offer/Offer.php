<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use Broadway\EventSourcing\EventSourcedAggregateRoot;
use CultureFeed_Cdb_Item_Base;
use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\LabelAwareAggregateRoot;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Media\Properties\Description as ImageDescription;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\CalendarNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Audience\AgeRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarWithSubEvents;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\VideoCollection;
use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Offer\Events\AbstractAvailableFromUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractBookingInfoUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractCalendarUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractContactPointUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractDescriptionDeleted;
use CultuurNet\UDB3\Offer\Events\AbstractDescriptionTranslated;
use CultuurNet\UDB3\Offer\Events\AbstractDescriptionUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractFacilitiesUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractGeoCoordinatesUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractLabelAdded;
use CultuurNet\UDB3\Offer\Events\AbstractLabelRemoved;
use CultuurNet\UDB3\Offer\Events\AbstractLabelsImported;
use CultuurNet\UDB3\Offer\Events\AbstractOfferDeleted;
use CultuurNet\UDB3\Offer\Events\AbstractOrganizerDeleted;
use CultuurNet\UDB3\Offer\Events\AbstractOrganizerUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractOwnerChanged;
use CultuurNet\UDB3\Offer\Events\AbstractPriceInfoUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractTitleTranslated;
use CultuurNet\UDB3\Offer\Events\AbstractTitleUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractTypeUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractTypicalAgeRangeDeleted;
use CultuurNet\UDB3\Offer\Events\AbstractTypicalAgeRangeUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractVideoDeleted;
use CultuurNet\UDB3\Offer\Events\AbstractVideoEvent;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImageAdded;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImageRemoved;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImagesEvent;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImagesImportedFromUDB2;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImagesUpdatedFromUDB2;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImageUpdated;
use CultuurNet\UDB3\Offer\Events\Image\AbstractMainImageSelected;
use CultuurNet\UDB3\Offer\Events\Moderation\AbstractApproved;
use CultuurNet\UDB3\Offer\Events\Moderation\AbstractFlaggedAsDuplicate;
use CultuurNet\UDB3\Offer\Events\Moderation\AbstractFlaggedAsInappropriate;
use CultuurNet\UDB3\Offer\Events\Moderation\AbstractPublished;
use CultuurNet\UDB3\Offer\Events\Moderation\AbstractRejected;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;

abstract class Offer extends EventSourcedAggregateRoot implements LabelAwareAggregateRoot
{
    public const DUPLICATE_REASON = 'duplicate';
    public const INAPPROPRIATE_REASON = 'inappropriate';

    protected LabelsArray $labels;

    protected ImageCollection $images;

    protected VideoCollection $videos;

    /**
     * Organizer ids can come from UDB2 which does not strictly use UUIDs.
     */
    protected ?string $organizerId = null;

    protected ?WorkflowStatus $workflowStatus = null;

    protected ?DateTimeInterface $availableFrom = null;

    protected ?string $rejectedReason = null;

    protected ?PriceInfo $priceInfo = null;

    /**
     * @var string[]
     */
    protected array $titles;

    /**
     * @var Description[]
     */
    protected array $descriptions;

    protected Language $mainLanguage;

    protected ?string $typeId = null;

    protected ?array $facilities = null;

    protected ?ContactPoint $contactPoint = null;

    protected ?Calendar $calendar = null;

    protected ?AgeRange $typicalAgeRange = null;

    protected ?BookingInfo $bookingInfo = null;

    private ?string $ownerId = null;

    /**
     * @var string[]
     */
    private array $importedLabelNames = [];

    public function __construct()
    {
        $this->titles = [];
        $this->descriptions = [];
        $this->labels = new LabelsArray();
        $this->images = new ImageCollection();
        $this->videos = new VideoCollection();
        $this->contactPoint = null;
        $this->calendar = null;
        $this->typicalAgeRange = null;
        $this->bookingInfo = null;
    }

    abstract public static function getOfferType(): OfferType;

    public function getLabels(): Labels
    {
        $labels = new Labels();

        foreach ($this->labels->toArray() as $label) {
            $labels = $labels->with(new Label(new LabelName($label['labelName']), $label['isVisible']));
        }

        return $labels;
    }

    public function getOrganizerId(): ?string
    {
        return $this->organizerId;
    }

    public function changeOwner(string $newOwnerId): void
    {
        // Will always be true for the first call to changeOwner() since we have no way to know who the creator was
        // inside the aggregate root. That's stored in the metadata of the DomainMessage, not the payload, and Broadway
        // does not pass that metadata to the apply...() methods.
        if ($this->ownerId !== $newOwnerId) {
            $this->apply($this->createOwnerChangedEvent($newOwnerId));
        }
    }

    protected function applyOwnerChanged(AbstractOwnerChanged $ownerChanged): void
    {
        $this->ownerId = $ownerChanged->getNewOwnerId();
    }

    abstract protected function createOwnerChangedEvent(string $newOwnerId): AbstractOwnerChanged;

    public function updateType(Category $category): void
    {
        if (!$this->typeId || $this->typeId !== $category->getId()->toString()) {
            $this->apply($this->createTypeUpdatedEvent($category));
        }
    }

    public function updateAllStatuses(Status $status): void
    {
        $updatedCalendar = $this->calendar->withStatus($status);

        if ($updatedCalendar instanceof CalendarWithSubEvents) {
            $updatedCalendar = $updatedCalendar->withStatusOnSubEvents($status);
        }

        $this->updateCalendar($updatedCalendar);
    }

    public function updateBookingAvailability(BookingAvailability $bookingAvailability): void
    {
        if (!$this->calendar instanceof CalendarWithSubEvents) {
            throw CalendarTypeNotSupported::forCalendarType($this->calendar->getType());
        }

        $updatedCalendar = $this->calendar
            ->withBookingAvailability($bookingAvailability);

        if ($updatedCalendar instanceof CalendarWithSubEvents) {
            $updatedCalendar = $updatedCalendar->withBookingAvailabilityOnSubEvents($bookingAvailability);
        }

        $this->updateCalendar($updatedCalendar);
    }

    /**
     * @param Category[] $facilities
     */
    public function updateFacilities(array $facilities): void
    {
        if ($this->facilities === null || !$this->sameFacilities($this->facilities, $facilities)) {
            $this->apply($this->createFacilitiesUpdatedEvent($facilities));
        }
    }

    protected function applyFacilitiesUpdated(AbstractFacilitiesUpdated $facilitiesUpdated): void
    {
        $this->facilities = $facilitiesUpdated->getFacilities();
    }

    /**
     * @param Category[] $facilities1
     * @param Category[] $facilities2
     */
    private function sameFacilities(array $facilities1, array $facilities2): bool
    {
        if (empty($facilities1) && empty($facilities2)) {
            return true;
        }

        if (count($facilities1) !== count($facilities2)) {
            return false;
        }

        $sameFacilities = array_uintersect(
            $facilities1,
            $facilities2,
            function (Category $facility1, Category $facility2) {
                return strcmp($facility1->getId()->toString(), $facility2->getId()->toString());
            }
        );

        return count($sameFacilities) === count($facilities2);
    }

    /**
     * Get the id of the main image if one is selected for this offer.
     */
    protected function getMainImageId(): ?Uuid
    {
        $mainImage = $this->images->getMain();
        return isset($mainImage) ? $mainImage->getMediaObjectId() : null;
    }

    public function addLabel(Label $label): void
    {
        if (!$this->labels->containsLabel($label->getName()->toString())) {
            $this->apply(
                $this->createLabelAddedEvent($label->getName()->toString(), $label->isVisible())
            );
        }
    }

    public function removeLabel(string $labelName): void
    {
        if ($this->labels->containsLabel($labelName)) {
            $this->apply(
                $this->createLabelRemovedEvent($labelName)
            );
        }
    }

    public function replaceLabels(Labels $importLabelsCollection): void
    {
        $this->processLabels($importLabelsCollection, false);
    }

    public function importLabels(Labels $importLabelsCollection): void
    {
        $this->processLabels($importLabelsCollection, true);
    }

    private function processLabels(Labels $importLabelsCollection, bool $importFlag): void
    {
        $keepLabelsCollection = new Labels();

        if ($importFlag === true) {
            // Always keep non-imported labels that are already on the offer

            foreach ($this->labels->toArray() as $label) {
                if (!in_array($label['labelName'], $this->importedLabelNames, true)) {
                    $keepLabelsCollection = $keepLabelsCollection->with(
                        new Label(new LabelName($label['labelName']), $label['isVisible'])
                    );
                }
            }
        }

        // What are the added labels?
        // Labels which are not inside the internal state but inside the imported labels
        $addedLabels = new Labels();
        foreach ($importLabelsCollection as $importedLabel) {
            $existingLabel = $this->labels->getLabel($importedLabel->getName()->toString());
            if ($existingLabel === null || $existingLabel['isVisible'] !== $importedLabel->isVisible()) {
                $addedLabels = $addedLabels->with($importedLabel);
            }
        }

        // Fire a LabelsImported for all new labels.
        $importLabels = new Labels();
        /** @var Label $addedLabel */
        foreach ($addedLabels->toArray() as $addedLabel) {
            $importLabels = $importLabels->with($addedLabel);
        }
        if ($importFlag || $importLabels->count() > 0) {
            if ($importFlag) {
                $this->apply(
                    $this->createLabelsImportedEvent($importLabels)
                );
            } else {
                $this->apply(
                    $this->createLabelsReplacedEvent($importLabels)
                );
            }
        }

        // What are the deleted labels?
        // Labels which are inside the internal state but not inside imported labels or labels to keep. (Taking
        // visibility into consideration.) For each deleted label fire a LabelDeleted event.
        foreach ($this->labels->toArray() as $label) {
            $label = new Label(new LabelName($label['labelName']), $label['isVisible']);
            $inImportWithSameVisibility = $importLabelsCollection->findLabel($label);
            $inImportWithDifferentVisibility = !$inImportWithSameVisibility &&
                $importLabelsCollection->findByName(new LabelName($label->getName()->toString()));
            $canBeRemoved = !$keepLabelsCollection->findLabel($label);
            if ((!$inImportWithSameVisibility && $canBeRemoved) || $inImportWithDifferentVisibility) {
                $this->apply($this->createLabelRemovedEvent($label->getName()->toString()));
            }
        }

        // For each added label fire a LabelAdded event.
        /** @var Label $label */
        foreach ($addedLabels->toArray() as $label) {
            $this->apply($this->createLabelAddedEvent($label->getName()->toString(), $label->isVisible()));
        }
    }

    public function updateTitle(Language $language, Title $title): void
    {
        if ($this->isTitleChanged($title, $language)) {
            if ($language->getCode() !== $this->mainLanguage->getCode()) {
                $event = $this->createTitleTranslatedEvent($language, $title);
            } else {
                $event = $this->createTitleUpdatedEvent($title);
            }

            $this->apply($event);
        }
    }

    public function applyTitleTranslated(AbstractTitleTranslated $titleTranslated): void
    {
        $this->titles[$titleTranslated->getLanguage()->getCode()] = $titleTranslated->getTitle();
    }

    public function applyTitleUpdated(AbstractTitleUpdated $titleUpdated): void
    {
        $this->titles[$this->mainLanguage->getCode()] = $titleUpdated->getTitle();
    }

    public function updateDescription(Description $description, Language $language): void
    {
        if ($this->isDescriptionChanged($description, $language)) {
            if ($language->getCode() !== $this->mainLanguage->getCode()) {
                $event = $this->createDescriptionTranslatedEvent($language, $description);
            } else {
                $event = $this->createDescriptionUpdatedEvent($description);
            }

            $this->apply($event);
        }
    }

    public function deleteDescription(Language $language): void
    {
        if (!isset($this->descriptions[$language->getCode()])) {
            return;
        }

        $this->apply($this->createDescriptionDeletedEvent($language));
    }

    public function updateCalendar(Calendar $calendar): void
    {
        if (is_null($this->calendar) || !$this->sameCalendars($this->calendar, $calendar)) {
            $this->apply(
                $this->createCalendarUpdatedEvent($calendar)
            );
        }
    }

    protected function applyCalendarUpdated(AbstractCalendarUpdated $calendarUpdated): void
    {
        $this->calendar = $calendarUpdated->getCalendar();
    }

    public function updateTypicalAgeRange(AgeRange $typicalAgeRange): void
    {
        $typicalAgeRangeUpdatedEvent = $this->createTypicalAgeRangeUpdatedEvent($typicalAgeRange);

        if (empty($this->typicalAgeRange) ||
            !($this->typicalAgeRange->toString() === $typicalAgeRangeUpdatedEvent->getTypicalAgeRange()->toString())) {
            $this->apply($typicalAgeRangeUpdatedEvent);
        }
    }

    protected function applyTypicalAgeRangeUpdated(AbstractTypicalAgeRangeUpdated $typicalAgeRangeUpdated): void
    {
        $this->typicalAgeRange = $typicalAgeRangeUpdated->getTypicalAgeRange();
    }

    public function deleteTypicalAgeRange(): void
    {
        if (!is_null($this->typicalAgeRange)) {
            $this->apply(
                $this->createTypicalAgeRangeDeletedEvent()
            );
        }
    }

    public function applyTypicalAgeRangeDeleted(AbstractTypicalAgeRangeDeleted $typicalAgeRangeDeleted): void
    {
        $this->typicalAgeRange = null;
    }

    public function updateOrganizer(string $organizerId): void
    {
        if ($this->organizerId !== $organizerId) {
            $this->apply(
                $this->createOrganizerUpdatedEvent($organizerId)
            );
        }
    }

    public function deleteOrganizer(string $organizerId): void
    {
        if ($this->organizerId === $organizerId) {
            $this->apply(
                $this->createOrganizerDeletedEvent($organizerId)
            );
        }
    }

    /**
     * Delete the current organizer regardless of the id.
     */
    public function deleteCurrentOrganizer(): void
    {
        if (!is_null($this->organizerId)) {
            $this->apply(
                $this->createOrganizerDeletedEvent($this->organizerId)
            );
        }
    }

    public function updateContactPoint(ContactPoint $contactPoint): void
    {
        if (is_null($this->contactPoint) || !$this->contactPoint->sameAs($contactPoint)) {
            $this->apply(
                $this->createContactPointUpdatedEvent($contactPoint)
            );
        }
    }

    protected function applyContactPointUpdated(AbstractContactPointUpdated $contactPointUpdated): void
    {
        $this->contactPoint = $contactPointUpdated->getContactPoint();
    }

    public function updateGeoCoordinates(Coordinates $coordinates): void
    {
        // Note: DON'T compare to previous coordinates and apply only on
        // changes. Various projectors expect GeoCoordinatesUpdated after
        // MajorInfoUpdated and PlaceUpdatedFromUDB2, even if the address
        // and thus the coordinates haven't actually changed.
        $this->apply(
            $this->createGeoCoordinatesUpdatedEvent($coordinates)
        );
    }

    public function updateBookingInfo(BookingInfo $bookingInfo): void
    {
        if (is_null($this->bookingInfo) || !$this->bookingInfo->sameAs($bookingInfo)) {
            $this->apply(
                $this->createBookingInfoUpdatedEvent($bookingInfo)
            );
        }
    }

    public function applyBookingInfoUpdated(AbstractBookingInfoUpdated $bookingInfoUpdated): void
    {
        $this->bookingInfo = $bookingInfoUpdated->getBookingInfo();
    }

    public function updatePriceInfo(PriceInfo $priceInfo): void
    {
        if (!is_null($this->priceInfo)) {
            $priceInfo = $priceInfo->withUiTPASTariffs($this->priceInfo->getUiTPASTariffs());
        }
        if (is_null($this->priceInfo) || $priceInfo->serialize() !== $this->priceInfo->serialize()) {
            $this->apply(
                $this->createPriceInfoUpdatedEvent($priceInfo)
            );
        }
    }

    protected function applyPriceInfoUpdated(AbstractPriceInfoUpdated $priceInfoUpdated): void
    {
        $this->priceInfo = $priceInfoUpdated->getPriceInfo();
    }

    protected function applyLabelAdded(AbstractLabelAdded $labelAdded): void
    {
        $this->labels->addLabel($labelAdded->getLabelName(), $labelAdded->isLabelVisible());
    }

    protected function applyLabelRemoved(AbstractLabelRemoved $labelRemoved): void
    {
        $this->labels->removeLabel($labelRemoved->getLabelName());

        $this->importedLabelNames = array_filter(
            $this->importedLabelNames,
            fn (string $importedLabelName) => $importedLabelName !== $labelRemoved->getLabelName()
        );
    }

    protected function applyLabelsImported(AbstractLabelsImported $labelsImported): void
    {
        foreach ($labelsImported->getAllLabelNames() as $importedLabelName) {
            if (!in_array($importedLabelName, $this->importedLabelNames, true)) {
                $this->importedLabelNames[] = $importedLabelName;
            }
        }
    }

    protected function applyTypeUpdated(AbstractTypeUpdated $themeUpdated): void
    {
        $this->typeId = $themeUpdated->getType()->getId()->toString();
    }

    protected function applyDescriptionUpdated(AbstractDescriptionUpdated $descriptionUpdated): void
    {
        $mainLanguageCode = $this->mainLanguage->getCode();
        $this->descriptions[$mainLanguageCode] = $descriptionUpdated->getDescription();
    }

    protected function applyDescriptionDeleted(AbstractDescriptionDeleted $descriptionDeleted): void
    {
        unset($this->descriptions[$descriptionDeleted->getLanguage()->getCode()]);
    }

    protected function applyDescriptionTranslated(AbstractDescriptionTranslated $descriptionTranslated): void
    {
        $languageCode = $descriptionTranslated->getLanguage()->getCode();
        $this->descriptions[$languageCode] = $descriptionTranslated->getDescription();
    }

    public function addImage(Image $image): void
    {
        // Find the image based on UUID inside the internal state.
        $existingImage = $this->images->findImageByUuid($image->getMediaObjectId());

        if ($existingImage === null) {
            $this->apply(
                $this->createImageAddedEvent($image)
            );
        }
    }

    public function updateImage(
        Uuid $mediaObjectId,
        ImageDescription $description,
        CopyrightHolder $copyrightHolder
    ): void {
        if ($this->updateImageAllowed($mediaObjectId, $description, $copyrightHolder)) {
            $this->apply(
                $this->createImageUpdatedEvent(
                    $mediaObjectId,
                    $description,
                    $copyrightHolder
                )
            );
        }
    }

    private function updateImageAllowed(
        Uuid $mediaObjectId,
        ImageDescription $description,
        CopyrightHolder $copyrightHolder
    ): bool {
        $image = $this->images->findImageByUuid($mediaObjectId);

        // Don't update if the image is not found based on UUID.
        if (!$image) {
            return false;
        }

        // Update when copyright or description is changed.
        return !$copyrightHolder->sameAs($image->getCopyrightHolder()) ||
            !$description->sameAs($image->getDescription());
    }

    public function removeImage(Image $image): void
    {
        // Find the image based on UUID inside the internal state.
        // Use the image from the internal state.
        $existingImage = $this->images->findImageByUuid($image->getMediaObjectId());

        if ($existingImage) {
            $this->apply(
                $this->createImageRemovedEvent($existingImage)
            );
        }
    }

    public function selectMainImage(Image $image): void
    {
        if (!$this->images->findImageByUuid($image->getMediaObjectId())) {
            throw new ImageMustBeLinkedException();
        }

        $oldMainImage = $this->images->getMain();

        if (!isset($oldMainImage) || $oldMainImage->getMediaObjectId() !== $image->getMediaObjectId()) {
            $this->apply(
                $this->createMainImageSelectedEvent($image)
            );
        }
    }

    public function importImages(ImageCollection $imageCollection): void
    {
        $currentImageCollection = $this->images;

        $oldMainImage = $this->images->getMain();
        $newMainImage = $imageCollection->getMain();

        $selectNewMainImage = isset($oldMainImage, $newMainImage) && !$oldMainImage->getMediaObjectId()->sameAs($newMainImage->getMediaObjectId());

        $importImages = $imageCollection->toArray();
        $currentImages = $currentImageCollection->toArray();

        $compareImages = function (Image $a, Image $b) {
            $idA = $a->getMediaObjectId()->toString();
            $idB = $b->getMediaObjectId()->toString();
            return strcmp($idA, $idB);
        };

        /* @var Image[] $addedImages */
        $addedImages = array_udiff($importImages, $currentImages, $compareImages);

        /* @var Image[] $updatedImages */
        $updatedImages = array_uintersect($importImages, $currentImages, $compareImages);

        /* @var Image[] $removedImages */
        $removedImages = array_udiff($currentImages, $importImages, $compareImages);

        foreach ($addedImages as $addedImage) {
            $this->apply($this->createImageAddedEvent($addedImage));
        }

        foreach ($updatedImages as $updatedImage) {
            if ($this->updateImageAllowed($updatedImage->getMediaObjectId(), $updatedImage->getDescription(), $updatedImage->getCopyrightHolder())) {
                $this->apply(
                    $this->createImageUpdatedEvent(
                        $updatedImage->getMediaObjectId(),
                        $updatedImage->getDescription(),
                        $updatedImage->getCopyrightHolder(),
                        $updatedImage->getLanguage()->getCode()
                    )
                );
            }
        }

        foreach ($removedImages as $removedImage) {
            $this->apply($this->createImageRemovedEvent($removedImage));
        }

        if ($selectNewMainImage) {
            $this->apply($this->createMainImageSelectedEvent($newMainImage));
        }
    }

    public function addVideo(Video $video): void
    {
        $videosWithSameId = $this->videos->filter(
            fn (Video $currentVideo) => $currentVideo->getId() === $video->getId()
        );

        if ($videosWithSameId->isEmpty()) {
            $this->apply($this->createVideoAddedEvent($video));
        }
    }

    public function updateVideo(string $videoId, ?Url $url, ?Language $language, ?CopyrightHolder $copyrightHolder): void
    {
        $videosWithSameId = $this->videos->filter(
            fn (Video $currentVideo) => $currentVideo->getId() === $videoId
        );

        if ($videosWithSameId->count() !== 1) {
            return;
        }

        /** @var Video $updatedVideo */
        $updatedVideo = $videosWithSameId->getFirst();

        if ($url) {
            $updatedVideo = $updatedVideo->withUrl($url);
        }

        if ($language) {
            $updatedVideo = $updatedVideo->withLanguage($language);
        }

        if ($copyrightHolder) {
            $updatedVideo = $updatedVideo->withCopyrightHolder($copyrightHolder);
        }

        if ($updatedVideo->sameAs($videosWithSameId->getFirst())) {
            return;
        }

        $this->apply($this->createVideoUpdatedEvent($updatedVideo));
    }

    public function deleteVideo(string $videoID): void
    {
        $videosWithSameId = $this->videos->filter(
            fn (Video $video) => $video->getId() === $videoID
        );

        if (!$videosWithSameId->isEmpty()) {
            $this->apply($this->createVideoDeletedEvent($videoID));
        }
    }

    public function importVideos(VideoCollection $importVideos): void
    {
        $videoCompareIds = static fn (Video $v1, Video $v2) => strcmp($v1->getId(), $v2->getId());

        $newVideos = array_udiff(
            $importVideos->toArray(),
            $this->videos->toArray(),
            $videoCompareIds
        );

        $deletedVideos = array_udiff(
            $this->videos->toArray(),
            $importVideos->toArray(),
            $videoCompareIds
        );

        $updatedVideos = array_uintersect(
            $importVideos->toArray(),
            $this->videos->toArray(),
            static function (Video $v1, Video $v2) {
                $cmp = strcmp($v1->getId(), $v2->getId());

                if ($cmp !== 0) {
                    return $cmp;
                }

                return $v1->sameAs($v2) ? 1 : 0;
            }
        );

        foreach ($newVideos as $newVideo) {
            $this->apply($this->createVideoAddedEvent($newVideo));
        }

        foreach ($deletedVideos as $deletedVideo) {
            $this->apply($this->createVideoDeletedEvent($deletedVideo->getId()));
        }

        foreach ($updatedVideos as $updatedVideo) {
            $this->apply($this->createVideoUpdatedEvent($updatedVideo));
        }
    }

    public function delete(): void
    {
        if (!$this->isDeleted()) {
            $this->apply(
                $this->createOfferDeletedEvent()
            );
        }
    }

    protected function importWorkflowStatus(CultureFeed_Cdb_Item_Base $cdbItem): void
    {
        $wfStatus = $cdbItem->getWfStatus();
        $workflowStatus = $wfStatus ? WorkflowStatus::fromCultureFeedWorkflowStatus($wfStatus) : WorkflowStatus::READY_FOR_VALIDATION();

        $this->workflowStatus = $workflowStatus;
    }

    public function updateAvailableFrom(DateTimeInterface $availableFrom): void
    {
        if ($availableFrom < new DateTimeImmutable()) {
            $availableFrom = new DateTimeImmutable();
        }

        // It is required to use `==` instead of `===` to compare DateTime objects in PHP
        if ($this->availableFrom == $availableFrom) {
            return;
        }

        $this->apply($this->createAvailableFromUpdatedEvent($availableFrom));
    }

    /**
     * Publish the offer when it has workflowStatus DRAFT.
     * Does nothing if the offer is already in READY_FOR_VALIDATION or APPROVED.
     * Throws if the workflowStatus is REJECTED or DELETED.
     */
    public function publish(\DateTimeInterface $publicationDate): void
    {
        if ($this->workflowStatus->sameAs(WorkflowStatus::READY_FOR_VALIDATION()) ||
            $this->workflowStatus->sameAs(WorkflowStatus::APPROVED())) {
            // Nothing left to do if the offer has already been published.
            // Approved is the next logical step from ready for validation. So also consider this to be handled.
            return;
        }

        if (!$this->workflowStatus->sameAs(WorkflowStatus::DRAFT())) {
            throw new InvalidWorkflowStatusTransition($this->workflowStatus, WorkflowStatus::READY_FOR_VALIDATION());
        }

        $this->apply($this->createPublishedEvent($publicationDate));
    }

    public function approve(): void
    {
        $this->guardApprove() ?: $this->apply($this->createApprovedEvent());
    }

    private function guardApprove(): bool
    {
        if ($this->workflowStatus->sameAs(WorkflowStatus::APPROVED())) {
            return true; // nothing left to do if the offer has already been approved
        }

        if (!$this->workflowStatus->sameAs(WorkflowStatus::READY_FOR_VALIDATION())) {
            throw new Exception('You can not approve an offer that is not ready for validation');
        }

        return false;
    }

    public function reject(string $reason): void
    {
        $this->guardRejection($reason) ?: $this->apply($this->createRejectedEvent($reason));
    }

    public function flagAsDuplicate(): void
    {
        $this->guardRejection(self::DUPLICATE_REASON) ?: $this->apply($this->createFlaggedAsDuplicate());
    }

    public function flagAsInappropriate(): void
    {
        $this->guardRejection(self::INAPPROPRIATE_REASON) ?: $this->apply($this->createFlaggedAsInappropriate());
    }

    private function guardRejection(string $reason): bool
    {
        if ($this->workflowStatus->sameAs(WorkflowStatus::REJECTED())) {
            if ($reason === $this->rejectedReason) {
                return true; // nothing left to do if the offer has already been rejected for the same reason
            }

            throw new Exception('The offer has already been rejected for another reason: ' . $this->rejectedReason);
        }

        if (!$this->workflowStatus->sameAs(WorkflowStatus::READY_FOR_VALIDATION())) {
            throw new Exception('You can not reject an offer that is not ready for validation');
        }

        return false;
    }

    private function isTitleChanged(Title $title, Language $language): bool
    {
        $languageCode = $language->getCode();

        return !isset($this->titles[$languageCode]) ||
            $title->toString() !== $this->titles[$languageCode];
    }

    private function isDescriptionChanged(Description $description, Language $language): bool
    {
        $languageCode = $language->getCode();

        return !isset($this->descriptions[$languageCode]) ||
            !$description->sameAs($this->descriptions[$languageCode]);
    }

    protected function isDeleted(): bool
    {
        return $this->workflowStatus && $this->workflowStatus->sameAs(WorkflowStatus::DELETED());
    }

    protected function sameCalendars(Calendar $calendar1, Calendar $calendar2): bool
    {
        $calendarNormalizer = new CalendarNormalizer();
        return $calendarNormalizer->normalize($calendar1) === $calendarNormalizer->normalize($calendar2);
    }

    /**
     * Overwrites or resets the main image and all media objects
     * by importing a new collection of images from UDB2.
     */
    public function importImagesFromUDB2(ImageCollection $images): void
    {
        $this->apply($this->createImagesImportedFromUDB2($images));
    }

    /**
     * Overwrites or resets the main image and all media objects
     * by updating with a new collection of images from UDB2.
     */
    public function updateImagesFromUDB2(ImageCollection $images): void
    {
        $this->apply($this->createImagesUpdatedFromUDB2($images));
    }

    protected function applyAvailableFromUpdated(AbstractAvailableFromUpdated $availableFromUpdated): void
    {
        $this->availableFrom = $availableFromUpdated->getAvailableFrom();
    }

    protected function applyPublished(AbstractPublished $published): void
    {
        $this->availableFrom = $published->getPublicationDate();
        $this->workflowStatus = WorkflowStatus::READY_FOR_VALIDATION();
    }

    protected function applyApproved(AbstractApproved $approved): void
    {
        $this->workflowStatus = WorkflowStatus::APPROVED();
    }

    protected function applyRejected(AbstractRejected $rejected): void
    {
        $this->rejectedReason = $rejected->getReason();
        $this->workflowStatus = WorkflowStatus::REJECTED();
    }

    protected function applyFlaggedAsDuplicate(AbstractFlaggedAsDuplicate $flaggedAsDuplicate): void
    {
        $this->rejectedReason = self::DUPLICATE_REASON;
        $this->workflowStatus = WorkflowStatus::REJECTED();
    }

    protected function applyFlaggedAsInappropriate(AbstractFlaggedAsInappropriate $flaggedAsInappropriate): void
    {
        $this->rejectedReason = self::INAPPROPRIATE_REASON;
        $this->workflowStatus = WorkflowStatus::REJECTED();
    }

    protected function applyImageAdded(AbstractImageAdded $imageAdded): void
    {
        $this->images = $this->images->with($imageAdded->getImage());
    }

    protected function applyImageUpdated(AbstractImageUpdated $imageUpdated): void
    {
        $image = $this->images->findImageByUuid(new Uuid($imageUpdated->getMediaObjectId()));

        $updatedImage = new Image(
            $image->getMediaObjectId(),
            $image->getMimeType(),
            new ImageDescription($imageUpdated->getDescription()),
            new CopyrightHolder($imageUpdated->getCopyrightHolder()),
            $image->getSourceLocation(),
            $image->getLanguage()
        );

        // Currently no other option to update an item inside a collection.
        $this->images = $this->images->without($image);
        $this->images = $this->images->with($updatedImage);
    }

    protected function applyImageRemoved(AbstractImageRemoved $imageRemoved): void
    {
        $this->images = $this->images->without($imageRemoved->getImage());
    }

    protected function applyMainImageSelected(AbstractMainImageSelected $mainImageSelected): void
    {
        $this->images = $this->images->withMain($mainImageSelected->getImage());
    }

    protected function applyVideoAdded(AbstractVideoEvent $videoAdded): void
    {
        $this->videos = $this->videos->with($videoAdded->getVideo());
    }

    protected function applyVideoDeleted(AbstractVideoDeleted $videoDeleted): void
    {
        $this->videos = $this->videos->filter(
            fn (Video $video) => $video->getId() !== $videoDeleted->getVideoId()
        );
    }

    protected function applyVideoUpdated(AbstractVideoEvent $videoUpdated): void
    {
        $videos = array_map(
            static fn (Video $video) => $video->getId() === $videoUpdated->getVideo()->getId() ? $videoUpdated->getVideo() : $video,
            $this->videos->toArray()
        );

        $this->videos = new VideoCollection(...$videos);
    }

    protected function applyOrganizerUpdated(AbstractOrganizerUpdated $organizerUpdated): void
    {
        $this->organizerId = $organizerUpdated->getOrganizerId();
    }

    protected function applyOrganizerDeleted(AbstractOrganizerDeleted $organizerDeleted): void
    {
        $this->organizerId = null;
    }

    protected function applyImagesImportedFromUDB2(AbstractImagesImportedFromUDB2 $imagesImportedFromUDB2): void
    {
        $this->applyUdb2ImagesEvent($imagesImportedFromUDB2);
    }

    protected function applyImagesUpdatedFromUDB2(AbstractImagesUpdatedFromUDB2 $imagesUpdatedFromUDB2): void
    {
        $this->applyUdb2ImagesEvent($imagesUpdatedFromUDB2);
    }

    /**
     * This indirect apply method can be called internally to deal with images coming from UDB2.
     * Imports from UDB2 only contain the native Dutch content.
     * @see https://github.com/cultuurnet/udb3-udb2-bridge/blob/db0a7ab2444f55bb3faae3d59b82b39aaeba253b/test/Media/ImageCollectionFactoryTest.php#L79-L103
     * Because of this we have to make sure translated images are left in place.
     */
    protected function applyUdb2ImagesEvent(AbstractImagesEvent $imagesEvent): void
    {
        $newMainImage = $imagesEvent->getImages()->getMain();
        $dutchImagesList = $imagesEvent->getImages()->toArray();
        $translatedImagesList = array_filter(
            $this->images->toArray(),
            function (Image $image) {
                return $image->getLanguage()->getCode() !== 'nl';
            }
        );

        $imagesList = array_merge($dutchImagesList, $translatedImagesList);
        $images = ImageCollection::fromArray($imagesList);

        $this->images = isset($newMainImage) ? $images->withMain($newMainImage) : $images;
    }

    abstract protected function createLabelAddedEvent(string $labelName, bool $isVisible): AbstractLabelAdded;

    abstract protected function createLabelRemovedEvent(string $labelName): AbstractLabelRemoved;

    abstract protected function createLabelsImportedEvent(Labels $labels): AbstractLabelsImported;

    abstract protected function createLabelsReplacedEvent(Labels $labels): AbstractLabelsImported;

    abstract protected function createTitleTranslatedEvent(
        Language $language,
        Title $title
    ): AbstractTitleTranslated;

    abstract protected function createDescriptionTranslatedEvent(
        Language $language,
        Description $description
    ): AbstractDescriptionTranslated;

    abstract protected function createImageAddedEvent(Image $image): AbstractImageAdded;

    abstract protected function createImageRemovedEvent(Image $image): AbstractImageRemoved;

    abstract protected function createImageUpdatedEvent(
        Uuid $uuid,
        ImageDescription $description,
        CopyrightHolder $copyrightHolder,
        ?string $language = null
    ): AbstractImageUpdated;

    abstract protected function createMainImageSelectedEvent(Image $image): AbstractMainImageSelected;

    abstract protected function createVideoAddedEvent(Video $video): AbstractVideoEvent;

    abstract protected function createVideoDeletedEvent(string $videoId): AbstractVideoDeleted;

    abstract protected function createVideoUpdatedEvent(Video $video): AbstractVideoEvent;

    abstract protected function createOfferDeletedEvent(): AbstractOfferDeleted;

    abstract protected function createTitleUpdatedEvent(Title $title): AbstractTitleUpdated;

    abstract protected function createDescriptionUpdatedEvent(Description $description): AbstractDescriptionUpdated;

    abstract protected function createDescriptionDeletedEvent(Language $language): AbstractDescriptionDeleted;

    abstract protected function createCalendarUpdatedEvent(Calendar $calendar): AbstractCalendarUpdated;

    abstract protected function createTypicalAgeRangeUpdatedEvent(AgeRange $typicalAgeRange): AbstractTypicalAgeRangeUpdated;

    abstract protected function createTypicalAgeRangeDeletedEvent(): AbstractTypicalAgeRangeDeleted;

    abstract protected function createOrganizerUpdatedEvent(string $organizerId): AbstractOrganizerUpdated;

    abstract protected function createOrganizerDeletedEvent(string $organizerId): AbstractOrganizerDeleted;

    abstract protected function createContactPointUpdatedEvent(ContactPoint $contactPoint): AbstractContactPointUpdated;

    abstract protected function createGeoCoordinatesUpdatedEvent(Coordinates $coordinates): AbstractGeoCoordinatesUpdated;

    abstract protected function createBookingInfoUpdatedEvent(BookingInfo $bookingInfo): AbstractBookingInfoUpdated;

    abstract protected function createPriceInfoUpdatedEvent(PriceInfo $priceInfo): AbstractPriceInfoUpdated;

    abstract protected function createAvailableFromUpdatedEvent(DateTimeInterface $availableFrom): AbstractAvailableFromUpdated;

    abstract protected function createPublishedEvent(\DateTimeInterface $publicationDate): AbstractPublished;

    abstract protected function createApprovedEvent(): AbstractApproved;

    abstract protected function createRejectedEvent(string $reason): AbstractRejected;

    abstract protected function createFlaggedAsDuplicate(): AbstractFlaggedAsDuplicate;

    abstract protected function createFlaggedAsInappropriate(): AbstractFlaggedAsInappropriate;

    abstract protected function createImagesImportedFromUDB2(ImageCollection $images): AbstractImagesImportedFromUDB2;

    abstract protected function createImagesUpdatedFromUDB2(ImageCollection $images): AbstractImagesUpdatedFromUDB2;

    abstract protected function createTypeUpdatedEvent(Category $type): AbstractTypeUpdated;

    abstract protected function createFacilitiesUpdatedEvent(array $facilities): AbstractFacilitiesUpdated;
}
