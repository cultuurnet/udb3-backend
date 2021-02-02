<?php

namespace CultuurNet\UDB3\Model\Organizer;

use CultuurNet\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Geography\TranslatedAddress;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

interface Organizer
{
    /**
     * @return UUID
     */
    public function getId();

    /**
     * @return Language
     */
    public function getMainLanguage();

    /**
     * @return TranslatedTitle
     */
    public function getName();

    /**
     * @return Url|null
     */
    public function getUrl();

    /**
     * @return TranslatedAddress|null
     */
    public function getAddress();

    /**
     * @return Coordinates|null
     */
    public function getGeoCoordinates();

    /**
     * @return Labels
     */
    public function getLabels();

    /**
     * @return ContactPoint
     */
    public function getContactPoint();
}
