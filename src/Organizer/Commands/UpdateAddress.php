<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

class UpdateAddress extends AbstractUpdateOrganizerCommand
{
    private Address $address;

    private Language $language;

    public function __construct(
        string $organizerId,
        Address $address,
        Language $language
    ) {
        parent::__construct($organizerId);
        $this->address = $address;
        $this->language = $language;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }
}
