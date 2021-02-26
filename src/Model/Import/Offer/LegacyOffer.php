<?php

namespace CultuurNet\UDB3\Model\Import\Offer;

use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\AgeRange;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;

interface LegacyOffer
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
     * @return Description|null
     */
    public function getDescription();

    /**
     * @return EventType
     */
    public function getType();

    /**
     * @return Theme|null
     */
    public function getTheme();

    /**
     * @return Calendar
     */
    public function getCalendar();

    /**
     * @return string|null
     */
    public function getOrganizerId();

    /**
     * @return AgeRange|null
     */
    public function getAgeRange();

    /**
     * @return PriceInfo|null
     */
    public function getPriceInfo();

    /**
     * @return BookingInfo|null
     */
    public function getBookingInfo();

    /**
     * @return ContactPoint|null
     */
    public function getContactPoint();

    /**
     * @return \DateTimeImmutable|null
     */
    public function getAvailableFrom(\DateTimeImmutable $default = null);

    /**
     * @return Title[]
     *   Language code as key, and Title as value.
     */
    public function getTitleTranslations();

    /**
     * @return Description[]
     *   Language code as key, and Description as value.
     */
    public function getDescriptionTranslations();
}
