<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Organizer;

use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Title;

class OrganizerCreationPayload
{
    private Language $mainLanguage;

    private Url $website;

    private Title $title;

    private Address $address;

    private ContactPoint $contactPoint;

    public function __construct(
        Language $mainLanguage,
        Url $website,
        Title $title,
        Address $address = null,
        ContactPoint $contactPoint = null
    ) {
        $this->mainLanguage = $mainLanguage;
        $this->website = $website;
        $this->title = $title;
        $this->address = $address;
        $this->contactPoint = $contactPoint;
    }

    public function getMainLanguage(): Language
    {
        return $this->mainLanguage;
    }

    public function getWebsite(): Url
    {
        return $this->website;
    }

    public function getTitle(): Title
    {
        return $this->title;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function getContactPoint(): ?ContactPoint
    {
        return $this->contactPoint;
    }
}
