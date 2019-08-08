<?php

namespace CultuurNet\UDB3\Model\Import\Organizer;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Title;
use ValueObjects\Web\Url;

interface LegacyOrganizer
{
    /**
     * @return string
     */
    public function getId();

    /**
     * @return Language
     */
    public function getMainLanguage();

    /**
     * @return Title
     */
    public function getTitle();

    /**
     * @return Url
     */
    public function getWebsite();

    /**
     * @return Address|null
     */
    public function getAddress();

    /**
     * @return ContactPoint|null
     */
    public function getContactPoint();

    /**
     * @return Title[]
     *   Language code as key, and Title as value.
     */
    public function getTitleTranslations();

    /**
     * @return Address[]
     *   Language code as key, and Address as value.
     */
    public function getAddressTranslations();
}
