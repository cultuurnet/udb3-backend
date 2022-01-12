<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use Broadway\EventSourcing\EventSourcedAggregateRoot;
use CultureFeed_Cdb_Data_Keyword;
use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Cdb\UpdateableWithCdbXmlInterface;
use CultuurNet\UDB3\LabelAwareAggregateRoot;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumbers;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Image;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Images;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedDescription;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use CultuurNet\UDB3\Organizer\Events\AddressRemoved;
use CultuurNet\UDB3\Organizer\Events\AddressTranslated;
use CultuurNet\UDB3\Organizer\Events\AddressUpdated;
use CultuurNet\UDB3\Organizer\Events\ContactPointUpdated;
use CultuurNet\UDB3\Organizer\Events\DescriptionUpdated;
use CultuurNet\UDB3\Organizer\Events\GeoCoordinatesUpdated;
use CultuurNet\UDB3\Organizer\Events\ImageAdded;
use CultuurNet\UDB3\Organizer\Events\ImageRemoved;
use CultuurNet\UDB3\Organizer\Events\ImageUpdated;
use CultuurNet\UDB3\Organizer\Events\LabelAdded;
use CultuurNet\UDB3\Organizer\Events\LabelRemoved;
use CultuurNet\UDB3\Organizer\Events\LabelsImported;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\Events\OrganizerDeleted;
use CultuurNet\UDB3\Organizer\Events\OrganizerImportedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\MainImageUpdated;
use CultuurNet\UDB3\Organizer\Events\OrganizerUpdatedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\TitleTranslated;
use CultuurNet\UDB3\Organizer\Events\TitleUpdated;
use CultuurNet\UDB3\Organizer\Events\WebsiteUpdated;
use OutOfBoundsException;

class Organizer extends EventSourcedAggregateRoot implements UpdateableWithCdbXmlInterface, LabelAwareAggregateRoot
{
    protected string $actorId;

    private Language $mainLanguage;

    private ?Url $website = null;

    /**
     * @var Title[]
     */
    private array $titles;

    private ?TranslatedDescription $translatedDescription = null;

    /**
     * @var Address[]|null
     */
    private ?array $addresses = null;

    private ContactPoint $contactPoint;

    private Images $images;

    private ?string $mainImageId = null;

    private Labels $labels;

    private WorkflowStatus $workflowStatus;

    public function getAggregateRootId(): string
    {
        return $this->actorId;
    }

    public function __construct()
    {
        // Contact points can be empty, but we only want to start recording
        // ContactPointUpdated events as soon as the organizer is updated
        // with a non-empty contact point. To enforce this we initialize the
        // aggregate state with an empty contact point.
        $this->contactPoint = new ContactPoint();
        $this->images = new Images();
        $this->labels = new Labels();
        $this->workflowStatus = WorkflowStatus::ACTIVE();
    }

    public static function importFromUDB2(
        string $actorId,
        string $cdbXml,
        string $cdbXmlNamespaceUri
    ): Organizer {
        $organizer = new self();
        $organizer->apply(
            new OrganizerImportedFromUDB2(
                $actorId,
                $cdbXml,
                $cdbXmlNamespaceUri
            )
        );

        return $organizer;
    }

    public static function create(
        string $id,
        Language $mainLanguage,
        Url $website,
        Title $title
    ): Organizer {
        $organizer = new self();

        $organizer->apply(
            new OrganizerCreatedWithUniqueWebsite(
                $id,
                $mainLanguage->toString(),
                $website->toString(),
                $title->toString()
            )
        );

        return $organizer;
    }

    public function updateWithCdbXml($cdbXml, $cdbXmlNamespaceUri): void
    {
        $this->apply(
            new OrganizerUpdatedFromUDB2(
                $this->actorId,
                $cdbXml,
                $cdbXmlNamespaceUri
            )
        );
    }

    public function updateWebsite(Url $website): void
    {
        if ($this->website === null || !$this->website->sameAs($website)) {
            $this->apply(
                new WebsiteUpdated(
                    $this->actorId,
                    $website->toString()
                )
            );
        }
    }

    public function updateTitle(
        Title $title,
        Language $language
    ): void {
        if ($this->isTitleChanged($title, $language)) {
            if (!$language->sameAs($this->mainLanguage)) {
                $event = new TitleTranslated(
                    $this->actorId,
                    $title->toString(),
                    $language->toString()
                );
            } else {
                $event = new TitleUpdated(
                    $this->actorId,
                    $title->toString()
                );
            }

            $this->apply($event);
        }
    }

    public function updateDescription(Description $description, Language $language): void
    {
        if ($this->needsDescriptionUpdate($description, $language)) {
            $this->apply(
                new DescriptionUpdated(
                    $this->actorId,
                    $description->toString(),
                    $language->toString()
                )
            );
        }
    }

    public function needsDescriptionUpdate(Description $description, Language $language): bool
    {
        if ($this->translatedDescription === null) {
            return true;
        }

        try {
            return !$this->translatedDescription->getTranslation($language)->sameAs($description);
        } catch (OutOfBoundsException $outOfBoundsException) {
            return true;
        }
    }

    public function updateAddress(
        Address $address,
        Language $language
    ): void {
        if ($this->isAddressChanged($address, $language)) {
            if (!$language->sameAs($this->mainLanguage)) {
                $event = new AddressTranslated(
                    $this->actorId,
                    $address->getStreet()->toString(),
                    $address->getPostalCode()->toString(),
                    $address->getLocality()->toString(),
                    $address->getCountryCode()->toString(),
                    $language->getCode()
                );
            } else {
                $event = new AddressUpdated(
                    $this->actorId,
                    $address->getStreet()->toString(),
                    $address->getPostalCode()->toString(),
                    $address->getLocality()->toString(),
                    $address->getCountryCode()->toString()
                );
            }

            $this->apply($event);
        }
    }

    public function removeAddress(): void
    {
        if (!$this->hasAddress()) {
            return;
        }

        $this->apply(
            new AddressRemoved($this->actorId)
        );
    }

    public function updateContactPoint(ContactPoint $contactPoint): void
    {
        if (!$this->contactPoint->sameAs($contactPoint)) {
            $this->apply(
                new ContactPointUpdated(
                    $this->actorId,
                    $contactPoint->getTelephoneNumbers()->toStringArray(),
                    $contactPoint->getEmailAddresses()->toStringArray(),
                    $contactPoint->getUrls()->toStringArray()
                )
            );
        }
    }

    public function addImage(Image $image): void
    {
        if ($this->hasImage($image->getId())) {
            return;
        }

        $this->apply(
            new ImageAdded(
                $this->actorId,
                $image->getId()->toString(),
                $image->getLanguage()->toString(),
                $image->getDescription()->toString(),
                $image->getCopyrightHolder()->toString()
            )
        );
    }

    public function updateImage(
        UUID $imageId,
        ?Language $language,
        ?Description $description,
        ?CopyrightHolder $copyrightHolder
    ): void {
        if (!$this->hasImage($imageId)) {
            return;
        }

        $images = $this->images->filter(
            fn (Image $currentImage) => $currentImage->getId()->sameAs($imageId)
        );

        if ($images->count() !== 1) {
            return;
        }

        /** @var Image $existingImage */
        $existingImage = $images->getFirst();

        $updatedImage = new Image(
            $imageId,
            $language ?: $existingImage->getLanguage(),
            $description ?: $existingImage->getDescription(),
            $copyrightHolder ?: $existingImage->getCopyrightHolder()
        );

        if ($updatedImage->sameAs($existingImage)) {
            return;
        }

        $this->apply(
            new ImageUpdated(
                $this->actorId,
                $imageId->toString(),
                $updatedImage->getLanguage()->toString(),
                $updatedImage->getDescription()->toString(),
                $updatedImage->getCopyrightHolder()->toString(),
            )
        );
    }

    public function updateMainImage(UUID $imageId): void
    {
        if ($this->needsUpdateMainImage($imageId)) {
            $this->apply(
                new MainImageUpdated($this->actorId, $imageId->toString())
            );
        }
    }

    private function needsUpdateMainImage(UUID $mainImageId): bool
    {
        // When the organizer has no images it can't be set as main.
        // If the organizer does not contain the main image as a normal image it can't be set as main.
        if (!$this->hasImage($mainImageId)) {
            return false;
        }

        // Only set it as main when there is a difference.
        // This is to prevent having events in the event store with no change.
        return $this->mainImageId !== $mainImageId->toString();
    }

    private function hasImage(UUID $imageId): bool
    {
        if ($this->images->isEmpty()) {
            return false;
        }

        return !$this->images->filter(
            fn (Image $currentImage) => $currentImage->getId()->sameAs($imageId)
        )->isEmpty();
    }

    public function removeImage(UUID $imageId): void
    {
        if (!$this->hasImage($imageId)) {
            return;
        }

        $this->apply(new ImageRemoved($this->actorId, $imageId->toString()));
    }

    public function updateGeoCoordinates(Coordinates $coordinate): void
    {
        $this->apply(
            new GeoCoordinatesUpdated(
                $this->actorId,
                $coordinate->getLatitude()->toDouble(),
                $coordinate->getLongitude()->toDouble()
            )
        );
    }

    public function addLabel(Label $label): void
    {
        $labelName = new LabelName($label->getName()->toString());

        if (!$this->hasLabelWithName($labelName)) {
            $this->apply(
                new LabelAdded(
                    $this->actorId,
                    $label->getName()->toString(),
                    $label->isVisible()
                )
            );
        }
    }

    public function removeLabel(Label $label): void
    {
        $labelName = new LabelName($label->getName()->toString());

        if ($this->hasLabelWithName($labelName)) {
            $this->apply(
                new LabelRemoved(
                    $this->actorId,
                    $label->getName()->toString(),
                    $label->isVisible()
                )
            );
        }
    }

    public function importLabels(Labels $labels, Labels $labelsToKeepIfAlreadyOnOrganizer): void
    {
        // Convert the imported labels to label collection.
        $importLabelsCollection = $labels;

        // Convert the labels to keep if already applied.
        $keepLabelsCollection = $labelsToKeepIfAlreadyOnOrganizer;

        // What are the added labels?
        // Labels which are not inside the internal state but inside the imported labels
        $addedLabels = new Labels();
        foreach ($importLabelsCollection->toArray() as $label) {
            if (!$this->labels->contains($label)) {
                $addedLabels = $addedLabels->with($label);
            }
        }

        // Fire a LabelsImported for all new labels.
        $importLabels = new Labels();
        foreach ($addedLabels->toArray() as $addedLabel) {
            $importLabels = $importLabels->with($addedLabel);
        }
        if ($importLabels->count() > 0) {
            $this->apply(new LabelsImported(
                $this->actorId,
                $importLabels
            ));
        }

        // For each added label fire a LabelAdded event.
        foreach ($addedLabels->toArray() as $label) {
            /** @var Label $label */
            $this->apply(new LabelAdded($this->actorId, $label->getName()->toString(), $label->isVisible()));
        }

        // What are the deleted labels?
        // Labels which are inside the internal state but not inside imported labels.
        // For each deleted label fire a LabelDeleted event.
        foreach ($this->labels->toArray() as $label) {
            if (!$importLabelsCollection->contains($label) && !$keepLabelsCollection->contains($label)) {
                $this->apply(new LabelRemoved($this->actorId, $label->getName()->toString(), $label->isVisible()));
            }
        }
    }

    public function delete(): void
    {
        if ($this->workflowStatus->sameAs(WorkflowStatus::ACTIVE())) {
            $this->apply(
                new OrganizerDeleted($this->getAggregateRootId())
            );
        }
    }

    protected function applyOrganizerCreated(OrganizerCreated $organizerCreated): void
    {
        $this->actorId = $organizerCreated->getOrganizerId();

        $this->mainLanguage = new Language('nl');

        $this->setTitle(new Title($organizerCreated->getTitle()), $this->mainLanguage);
    }

    protected function applyOrganizerCreatedWithUniqueWebsite(OrganizerCreatedWithUniqueWebsite $organizerCreated): void
    {
        $this->actorId = $organizerCreated->getOrganizerId();

        $this->mainLanguage = new Language($organizerCreated->getMainLanguage());

        $this->website = new Url($organizerCreated->getWebsite());

        $this->setTitle(
            new Title($organizerCreated->getTitle()),
            $this->mainLanguage
        );
    }

    /**
     * @throws \CultureFeed_Cdb_ParseException
     */
    protected function applyOrganizerImportedFromUDB2(
        OrganizerImportedFromUDB2 $organizerImported
    ): void {
        $this->actorId = (string) $organizerImported->getActorId();

        // On import from UDB2 the default main language is 'nl'.
        $this->mainLanguage = new Language('nl');

        $actor = ActorItemFactory::createActorFromCdbXml(
            $organizerImported->getCdbXmlNamespaceUri(),
            $organizerImported->getCdbXml()
        );

        $this->setTitle($this->getTitle($actor), $this->mainLanguage);

        $this->labels = $this->keywordsToLabels($actor->getKeywords(true));
    }

    /**
     * @throws \CultureFeed_Cdb_ParseException
     */
    protected function applyOrganizerUpdatedFromUDB2(
        OrganizerUpdatedFromUDB2 $organizerUpdatedFromUDB2
    ): void {
        // Note: never change the main language on update from UDB2.

        $actor = ActorItemFactory::createActorFromCdbXml(
            $organizerUpdatedFromUDB2->getCdbXmlNamespaceUri(),
            $organizerUpdatedFromUDB2->getCdbXml()
        );

        $this->setTitle($this->getTitle($actor), $this->mainLanguage);

        $this->labels = $this->keywordsToLabels($actor->getKeywords(true));
    }

    protected function applyWebsiteUpdated(WebsiteUpdated $websiteUpdated): void
    {
        $this->website = new Url($websiteUpdated->getWebsite());
    }

    protected function applyTitleUpdated(TitleUpdated $titleUpdated): void
    {
        $this->setTitle(
            new Title($titleUpdated->getTitle()),
            $this->mainLanguage
        );
    }

    protected function applyTitleTranslated(TitleTranslated $titleTranslated): void
    {
        $this->setTitle(
            new Title($titleTranslated->getTitle()),
            new Language($titleTranslated->getLanguage())
        );
    }

    protected function applyDescriptionUpdated(DescriptionUpdated $descriptionUpdated): void
    {
        $language = new Language($descriptionUpdated->getLanguage());
        $description = new Description($descriptionUpdated->getDescription());

        if ($this->translatedDescription === null) {
            $this->translatedDescription = new TranslatedDescription($language, $description);
            return;
        }

        $this->translatedDescription->withTranslation($language, $description);
    }

    protected function applyAddressUpdated(AddressUpdated $addressUpdated): void
    {
        $this->setAddress(new Address(
            new Street($addressUpdated->getStreetAddress()),
            new PostalCode($addressUpdated->getPostalCode()),
            new Locality($addressUpdated->getLocality()),
            new CountryCode($addressUpdated->getCountryCode())
        ), $this->mainLanguage);
    }

    protected function applyAddressRemoved(AddressRemoved $addressRemoved): void
    {
        $this->addresses = null;
    }

    protected function applyAddressTranslated(AddressTranslated $addressTranslated): void
    {
        $this->setAddress(
            new Address(
                new Street($addressTranslated->getStreetAddress()),
                new PostalCode($addressTranslated->getPostalCode()),
                new Locality($addressTranslated->getLocality()),
                new CountryCode($addressTranslated->getCountryCode())
            ),
            new Language($addressTranslated->getLanguage())
        );
    }

    protected function applyContactPointUpdated(ContactPointUpdated $contactPointUpdated): void
    {
        $this->contactPoint = new ContactPoint(
            new TelephoneNumbers(
                ...array_map(
                    fn (string $phone) => new TelephoneNumber($phone),
                    $contactPointUpdated->getPhones()
                )
            ),
            new EmailAddresses(
                ...array_map(
                    fn (string $email) => new EmailAddress($email),
                    $contactPointUpdated->getEmails()
                )
            ),
            new Urls(
                ...array_map(
                    fn (string $url) => new Url($url),
                    $contactPointUpdated->getUrls()
                )
            )
        );
    }

    protected function applyImageAdded(ImageAdded $imageAdded): void
    {
        $this->images = $this->images->with($imageAdded->getImage());

        if ($this->mainImageId === null) {
            $this->mainImageId = $imageAdded->getImage()->getId()->toString();
        }
    }

    protected function applyImageUpdated(ImageUpdated $imageUpdated): void
    {
        $images = array_map(
            fn (Image $image) =>
                $image->getId()->toString() === $imageUpdated->getImageId() ? $imageUpdated->getImage() : $image,
            $this->images->toArray()
        );

        $this->images = new Images(...$images);
    }

    protected function applyImageRemoved(ImageRemoved $imageRemoved): void
    {
        $this->images = $this->images->filter(
            fn (Image $image) => $image->getId()->toString() !== $imageRemoved->getImageId()
        );

        if ($this->images->isEmpty()) {
            $this->mainImageId = null;
            return;
        }

        if ($imageRemoved->getImageId() === $this->mainImageId) {
            /** @var Image $firstImage */
            $firstImage = $this->images->getFirst();
            $this->mainImageId = $firstImage->getId()->toString();
        }
    }

    protected function applyMainImageUpdated(MainImageUpdated $organizerUpdated): void
    {
        $this->mainImageId = $organizerUpdated->getMainImageId();
    }

    protected function applyLabelAdded(LabelAdded $labelAdded): void
    {
        $this->labels = $this->labels->with(
            new Label(
                new LabelName($labelAdded->getLabelName()),
                $labelAdded->isLabelVisible()
            )
        );
    }

    protected function applyLabelRemoved(LabelRemoved $labelRemoved): void
    {
        $this->labels = $this->labels->filter(
            fn (Label $label) => $label->getName()->toString() !== $labelRemoved->getLabelName()
        );
    }

    protected function applyOrganizerDeleted(OrganizerDeleted $organizerDeleted): void
    {
        $this->workflowStatus = WorkflowStatus::DELETED();
    }

    private function getTitle(\CultureFeed_Cdb_Item_Actor $actor): ?Title
    {
        $details = $actor->getDetails();
        $details->rewind();

        // The first language detail found will be used to retrieve
        // properties from which in UDB3 are not any longer considered
        // to be language specific.
        if ($details->valid()) {
            return new Title($details->current()->getTitle());
        }

        return null;
    }

    private function setTitle(Title $title, Language $language): void
    {
        $this->titles[$language->toString()] = $title;
    }

    private function isTitleChanged(Title $title, Language $language): bool
    {
        return !isset($this->titles[$language->getCode()]) ||
            $title->toString() !== $this->titles[$language->getCode()]->toString();
    }

    private function setAddress(Address $address, Language $language): void
    {
        $this->addresses[$language->toString()] = $address;
    }

    private function isAddressChanged(Address $address, Language $language): bool
    {
        return !isset($this->addresses[$language->getCode()]) ||
            !$address->sameAs($this->addresses[$language->getCode()]);
    }

    private function hasAddress(): bool
    {
        return $this->addresses !== null;
    }

    private function hasLabelWithName(LabelName $labelName): bool
    {
        $foundLabels = $this->labels->filter(
            fn (Label $currentLabel) => $currentLabel->getName()->sameAs($labelName)
        );

        return !$foundLabels->isEmpty();
    }

    /**
     * @param CultureFeed_Cdb_Data_Keyword[] $keywords
     */
    private function keywordsToLabels(array $keywords): Labels
    {
        return new Labels(
            ...array_map(
                fn (CultureFeed_Cdb_Data_Keyword $keyword) => new Label(
                    new LabelName($keyword->getValue()),
                    $keyword->isVisible()
                ),
                array_values($keywords)
            )
        );
    }
}
