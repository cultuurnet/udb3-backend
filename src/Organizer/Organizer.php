<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use Broadway\EventSourcing\EventSourcedAggregateRoot;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Address\Address as LegacyAddress;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Cdb\UpdateableWithCdbXmlInterface;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\LabelAwareAggregateRoot;
use CultuurNet\UDB3\LabelCollection;
use CultuurNet\UDB3\Language as LegacyLanguage;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Organizer\Events\AddressRemoved;
use CultuurNet\UDB3\Organizer\Events\AddressTranslated;
use CultuurNet\UDB3\Organizer\Events\AddressUpdated;
use CultuurNet\UDB3\Organizer\Events\ContactPointUpdated;
use CultuurNet\UDB3\Organizer\Events\GeoCoordinatesUpdated;
use CultuurNet\UDB3\Organizer\Events\LabelAdded;
use CultuurNet\UDB3\Organizer\Events\LabelRemoved;
use CultuurNet\UDB3\Organizer\Events\LabelsImported;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\Events\OrganizerDeleted;
use CultuurNet\UDB3\Organizer\Events\OrganizerImportedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\OrganizerUpdatedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\TitleTranslated;
use CultuurNet\UDB3\Organizer\Events\TitleUpdated;
use CultuurNet\UDB3\Organizer\Events\WebsiteUpdated;
use CultuurNet\UDB3\Title as LegacyTitle;
use ValueObjects\Geography\Country;
use ValueObjects\Geography\CountryCode;
use ValueObjects\Web\Url as LegacyUrl;

class Organizer extends EventSourcedAggregateRoot implements UpdateableWithCdbXmlInterface, LabelAwareAggregateRoot
{
    protected string $actorId;

    private LegacyLanguage $mainLanguage;

    private ?LegacyUrl $website = null;

    /**
     * @var LegacyTitle[]
     */
    private array $titles;

    /**
     * @var LegacyAddress[]|null
     */
    private ?array $addresses = null;

    private ContactPoint $contactPoint;

    private LabelCollection $labels;

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
        $this->labels = new LabelCollection();
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
        LegacyLanguage $mainLanguage,
        LegacyUrl $website,
        LegacyTitle $title
    ): Organizer {
        $organizer = new self();

        $organizer->apply(
            new OrganizerCreatedWithUniqueWebsite($id, (string) $mainLanguage, (string) $website, (string) $title)
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
        $newWebsite = LegacyUrl::fromNative($website->toString());

        if (is_null($this->website) || !$this->website->sameValueAs($newWebsite)) {
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
            if ($language->getCode() !== $this->mainLanguage->getCode()) {
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

    public function updateAddress(
        Address $address,
        Language $language
    ): void {
        if ($this->isAddressChanged(LegacyAddress::fromUdb3ModelAddress($address), $language)) {
            if ($language->getCode() !== $this->mainLanguage->getCode()) {
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
                    $contactPoint->getPhones(),
                    $contactPoint->getEmails(),
                    $contactPoint->getUrls()
                )
            );
        }
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
        if (!$this->labels->contains($label)) {
            $this->apply(new LabelAdded($this->actorId, (string) $label->getName(), $label->isVisible()));
        }
    }

    public function removeLabel(Label $label): void
    {
        if ($this->labels->contains($label)) {
            $this->apply(new LabelRemoved($this->actorId, (string) $label->getName(), $label->isVisible()));
        }
    }

    public function importLabels(Labels $labels, Labels $labelsToKeepIfAlreadyOnOrganizer): void
    {
        $convertLabelClass = function (\CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label $label) {
            return new Label(
                $label->getName()->toString(),
                $label->isVisible()
            );
        };

        // Convert the imported labels to label collection.
        $importLabelsCollection = new LabelCollection(
            array_map($convertLabelClass, $labels->toArray())
        );

        // Convert the labels to keep if already applied.
        $keepLabelsCollection = new LabelCollection(
            array_map($convertLabelClass, $labelsToKeepIfAlreadyOnOrganizer->toArray())
        );

        // What are the added labels?
        // Labels which are not inside the internal state but inside the imported labels
        $addedLabels = new LabelCollection();
        foreach ($importLabelsCollection->asArray() as $label) {
            if (!$this->labels->contains($label)) {
                $addedLabels = $addedLabels->with($label);
            }
        }

        // Fire a LabelsImported for all new labels.
        $importLabels = new Labels();
        foreach ($addedLabels->asArray() as $addedLabel) {
            $importLabels = $importLabels->with(
                new \CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label(
                    new LabelName((string) $addedLabel),
                    $addedLabel->isVisible()
                )
            );
        }
        if ($importLabels->count() > 0) {
            $this->apply(new LabelsImported(
                $this->actorId,
                $importLabels
            ));
        }

        // For each added label fire a LabelAdded event.
        foreach ($addedLabels->asArray() as $label) {
            $this->apply(new LabelAdded($this->actorId, (string) $label->getName(), $label->isVisible()));
        }

        // What are the deleted labels?
        // Labels which are inside the internal state but not inside imported labels.
        // For each deleted label fire a LabelDeleted event.
        foreach ($this->labels->asArray() as $label) {
            if (!$importLabelsCollection->contains($label) && !$keepLabelsCollection->contains($label)) {
                $this->apply(new LabelRemoved($this->actorId, (string) $label->getName(), $label->isVisible()));
            }
        }
    }

    public function delete(): void
    {
        if ($this->workflowStatus->is(WorkflowStatus::ACTIVE)) {
            $this->apply(
                new OrganizerDeleted($this->getAggregateRootId())
            );
        }
    }

    protected function applyOrganizerCreated(OrganizerCreated $organizerCreated): void
    {
        $this->actorId = $organizerCreated->getOrganizerId();

        $this->mainLanguage = new LegacyLanguage('nl');

        $this->setTitle(new LegacyTitle($organizerCreated->getTitle()), $this->mainLanguage);
    }

    protected function applyOrganizerCreatedWithUniqueWebsite(OrganizerCreatedWithUniqueWebsite $organizerCreated): void
    {
        $this->actorId = $organizerCreated->getOrganizerId();

        $this->mainLanguage = new LegacyLanguage($organizerCreated->getMainLanguage());

        $this->website =  LegacyUrl::fromNative($organizerCreated->getWebsite());

        $this->setTitle(
            new LegacyTitle($organizerCreated->getTitle()),
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
        $this->mainLanguage = new LegacyLanguage('nl');

        $actor = ActorItemFactory::createActorFromCdbXml(
            $organizerImported->getCdbXmlNamespaceUri(),
            $organizerImported->getCdbXml()
        );

        $this->setTitle($this->getTitle($actor), $this->mainLanguage);

        $this->labels = LabelCollection::fromKeywords($actor->getKeywords(true));
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

        $this->labels = LabelCollection::fromKeywords($actor->getKeywords(true));
    }

    protected function applyWebsiteUpdated(WebsiteUpdated $websiteUpdated): void
    {
        $this->website = LegacyUrl::fromNative($websiteUpdated->getWebsite());
    }

    protected function applyTitleUpdated(TitleUpdated $titleUpdated): void
    {
        $this->setTitle(
            new LegacyTitle($titleUpdated->getTitle()),
            $this->mainLanguage
        );
    }

    protected function applyTitleTranslated(TitleTranslated $titleTranslated): void
    {
        $this->setTitle(
            new LegacyTitle($titleTranslated->getTitle()),
            new LegacyLanguage($titleTranslated->getLanguage())
        )
        ;
    }

    protected function applyAddressUpdated(AddressUpdated $addressUpdated): void
    {
        $this->setAddress(new LegacyAddress(
            new Street($addressUpdated->getStreetAddress()),
            new PostalCode($addressUpdated->getPostalCode()),
            new Locality($addressUpdated->getLocality()),
            new Country(CountryCode::fromNative($addressUpdated->getCountryCode()))
        ), $this->mainLanguage);
    }

    protected function applyAddressRemoved(AddressRemoved $addressRemoved): void
    {
        $this->addresses = null;
    }

    protected function applyAddressTranslated(AddressTranslated $addressTranslated): void
    {
        $this->setAddress(
            new LegacyAddress(
                new Street($addressTranslated->getStreetAddress()),
                new PostalCode($addressTranslated->getPostalCode()),
                new Locality($addressTranslated->getLocality()),
                new Country(CountryCode::fromNative($addressTranslated->getCountryCode()))
            ),
            new LegacyLanguage($addressTranslated->getLanguage())
        );
    }

    protected function applyContactPointUpdated(ContactPointUpdated $contactPointUpdated): void
    {
        $this->contactPoint = new ContactPoint(
            $contactPointUpdated->getPhones(),
            $contactPointUpdated->getEmails(),
            $contactPointUpdated->getUrls()
        );
    }

    protected function applyLabelAdded(LabelAdded $labelAdded): void
    {
        $this->labels = $this->labels->with($labelAdded->getLabel());
    }

    protected function applyLabelRemoved(LabelRemoved $labelRemoved): void
    {
        $this->labels = $this->labels->without($labelRemoved->getLabel());
    }

    protected function applyOrganizerDeleted(OrganizerDeleted $organizerDeleted): void
    {
        $this->workflowStatus = WorkflowStatus::DELETED();
    }

    private function getTitle(\CultureFeed_Cdb_Item_Actor $actor): ?LegacyTitle
    {
        $details = $actor->getDetails();
        $details->rewind();

        // The first language detail found will be used to retrieve
        // properties from which in UDB3 are not any longer considered
        // to be language specific.
        if ($details->valid()) {
            return new LegacyTitle($details->current()->getTitle());
        }

        return null;
    }

    private function setTitle(LegacyTitle $title, LegacyLanguage $language): void
    {
        $this->titles[$language->getCode()] = $title;
    }

    private function isTitleChanged(Title $title, Language $language): bool
    {
        return !isset($this->titles[$language->getCode()]) ||
            $title->toString() !== $this->titles[$language->getCode()]->toNative();
    }

    private function setAddress(LegacyAddress $address, LegacyLanguage $language): void
    {
        $this->addresses[$language->getCode()] = $address;
    }

    private function isAddressChanged(LegacyAddress $address, Language $language): bool
    {
        return !isset($this->addresses[$language->getCode()]) ||
            !$address->sameAs($this->addresses[$language->getCode()]);
    }

    private function hasAddress(): bool
    {
        return $this->addresses !== null;
    }
}
