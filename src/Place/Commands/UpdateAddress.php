<?php

namespace CultuurNet\UDB3\Place\Commands;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;

class UpdateAddress extends AbstractCommand
{
    /**
     * @var Address
     */
    private $address;

    /**
     * @var Language
     */
    private $language;

    /**
     * @param string $itemId
     * @param Address $address
     * @param Language $language
     */
    public function __construct($itemId, Address $address, Language $language)
    {
        parent::__construct($itemId);
        $this->address = $address;
        $this->language = $language;
    }

    /**
     * @return Address
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @return Language
     */
    public function getLanguage()
    {
        return $this->language;
    }
}
