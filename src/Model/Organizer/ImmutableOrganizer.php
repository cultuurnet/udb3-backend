<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Organizer;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Geography\TranslatedAddress;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

class ImmutableOrganizer implements Organizer
{
    private UUID $id;

    private Language $mainLanguage;

    private TranslatedTitle $name;

    private ?Url $url;

    private ?TranslatedAddress $address = null;

    private ?Coordinates $coordinates = null;

    private Labels $labels;

    private ContactPoint $contactPoint;

    /**
     * @param Url|null $url
     *  When creating a new organizer a url is required.
     *  But for older organizers the url was not required.
     *  So there is a mix of organizers with and without url.
     */
    public function __construct(
        UUID $id,
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
    }

    public function getId(): UUID
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

    /**
     * @return ImmutableOrganizer
     */
    public function withName(TranslatedTitle $name)
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

    /**
     * @return ImmutableOrganizer
     */
    public function withUrl(Url $url)
    {
        $c = clone $this;
        $c->url = $url;
        return $c;
    }

    public function getAddress(): ?TranslatedAddress
    {
        return $this->address;
    }

    /**
     * @return ImmutableOrganizer
     */
    public function withAddress(TranslatedAddress $address)
    {
        $c = clone $this;
        $c->address = $address;
        return $c;
    }

    /**
     * @return ImmutableOrganizer
     */
    public function withoutAddress()
    {
        $c = clone $this;
        $c->address = null;
        return $c;
    }

    public function getGeoCoordinates(): ?Coordinates
    {
        return $this->coordinates;
    }

    /**
     * @return ImmutableOrganizer
     */
    public function withGeoCoordinates(Coordinates $coordinates)
    {
        $c = clone $this;
        $c->coordinates = $coordinates;
        return $c;
    }

    /**
     * @return ImmutableOrganizer
     */
    public function withoutGeoCoordinates()
    {
        $c = clone $this;
        $c->coordinates = null;
        return $c;
    }

    public function getLabels(): Labels
    {
        return $this->labels;
    }

    /**
     * @return ImmutableOrganizer
     */
    public function withLabels(Labels $labels)
    {
        $c = clone $this;
        $c->labels = $labels;
        return $c;
    }

    public function getContactPoint(): ContactPoint
    {
        return $this->contactPoint;
    }

    /**
     * @return ImmutableOrganizer
     */
    public function withContactPoint(ContactPoint $contactPoint)
    {
        $c = clone $this;
        $c->contactPoint = $contactPoint;
        return $c;
    }
}
