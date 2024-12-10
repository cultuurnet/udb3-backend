<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Organizer;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Geography\TranslatedAddress;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Images;
use CultuurNet\UDB3\Model\ValueObject\Moderation\Organizer\WorkflowStatus;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedDescription;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

class ImmutableOrganizer implements Organizer
{
    private Uuid $id;

    private Language $mainLanguage;

    private TranslatedTitle $name;

    private ?Url $url;

    private ?TranslatedDescription $description = null;

    private ?TranslatedDescription $educationalDescription = null;

    private ?TranslatedAddress $address = null;

    private ?Coordinates $coordinates = null;

    private Labels $labels;

    private ContactPoint $contactPoint;

    private Images $images;

    private WorkflowStatus $workflowStatus;

    /**
     * @param Url|null $url
     *  When creating a new organizer a url is required.
     *  But for older organizers the url was not required.
     *  So there is a mix of organizers with and without url.
     */
    public function __construct(
        Uuid $id,
        Language $mainLanguage,
        TranslatedTitle $name,
        Url $url = null
    ) {
        $this->id = $id;
        $this->mainLanguage = $mainLanguage;
        $this->name = $name;
        $this->url = $url;

        $this->labels = new Labels();
        $this->contactPoint = new ContactPoint();
        $this->images = new Images();
        $this->workflowStatus = WorkflowStatus::ACTIVE();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getMainLanguage(): Language
    {
        return $this->mainLanguage;
    }

    public function getName(): TranslatedTitle
    {
        return $this->name;
    }

    public function withName(TranslatedTitle $name): ImmutableOrganizer
    {
        $c = clone $this;
        $c->name = $name;
        $c->mainLanguage = $name->getOriginalLanguage();
        return $c;
    }

    public function getUrl(): ?Url
    {
        return $this->url;
    }

    public function withUrl(Url $url): ImmutableOrganizer
    {
        $c = clone $this;
        $c->url = $url;
        return $c;
    }

    public function getDescription(): ?TranslatedDescription
    {
        return $this->description;
    }

    public function withDescription(TranslatedDescription $description): ImmutableOrganizer
    {
        $c = clone $this;
        $c->description = $description;
        return $c;
    }

    public function getEducationalDescription(): ?TranslatedDescription
    {
        return $this->educationalDescription;
    }

    public function withEducationalDescription(TranslatedDescription $educationalDescription): ImmutableOrganizer
    {
        $c = clone $this;
        $c->educationalDescription = $educationalDescription;
        return $c;
    }

    public function getAddress(): ?TranslatedAddress
    {
        return $this->address;
    }

    public function withAddress(TranslatedAddress $address): ImmutableOrganizer
    {
        $c = clone $this;
        $c->address = $address;
        return $c;
    }

    public function withoutAddress(): ImmutableOrganizer
    {
        $c = clone $this;
        $c->address = null;
        return $c;
    }

    public function getGeoCoordinates(): ?Coordinates
    {
        return $this->coordinates;
    }

    public function withGeoCoordinates(Coordinates $coordinates): ImmutableOrganizer
    {
        $c = clone $this;
        $c->coordinates = $coordinates;
        return $c;
    }

    public function withoutGeoCoordinates(): ImmutableOrganizer
    {
        $c = clone $this;
        $c->coordinates = null;
        return $c;
    }

    public function getLabels(): Labels
    {
        return $this->labels;
    }

    public function withLabels(Labels $labels): ImmutableOrganizer
    {
        $c = clone $this;
        $c->labels = $labels;
        return $c;
    }

    public function getContactPoint(): ContactPoint
    {
        return $this->contactPoint;
    }

    public function withContactPoint(ContactPoint $contactPoint): ImmutableOrganizer
    {
        $c = clone $this;
        $c->contactPoint = $contactPoint;
        return $c;
    }

    public function getImages(): Images
    {
        return $this->images;
    }

    public function withImages(Images $images): ImmutableOrganizer
    {
        $c = clone $this;
        $c->images = $images;
        return $c;
    }

    public function getWorkflowStatus(): WorkflowStatus
    {
        return $this->workflowStatus;
    }

    public function withWorkflowStatus(WorkflowStatus $workflowStatus): ImmutableOrganizer
    {
        $c = clone $this;
        $c->workflowStatus = $workflowStatus;
        return $c;
    }
}
