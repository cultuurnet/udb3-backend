<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Commands;

use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;

class UpdateAddress extends AbstractCommand
{
    private Address $address;

    private Language $language;

    public function __construct(string $itemId, Address $address, Language $language)
    {
        parent::__construct($itemId);
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
