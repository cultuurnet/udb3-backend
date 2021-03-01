<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Offer;

use CultuurNet\UDB3\Model\Organizer\OrganizerReference;
use CultuurNet\UDB3\Model\ValueObject\Audience\AgeRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\MediaObjectReferences;
use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedDescription;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

interface Offer
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
    public function getTitle();

    /**
     * @return TranslatedDescription|null
     */
    public function getDescription();

    /**
     * @return Calendar
     */
    public function getCalendar();

    /**
     * @return Categories
     */
    public function getTerms();

    /**
     * @return Labels
     */
    public function getLabels();

    /**
     * @return OrganizerReference|null
     */
    public function getOrganizerReference();

    /**
     * @return AgeRange|null
     */
    public function getAgeRange();

    /**
     * @return PriceInfo|null
     */
    public function getPriceInfo();

    /**
     * @return BookingInfo
     */
    public function getBookingInfo();

    /**
     * @return ContactPoint
     */
    public function getContactPoint();

    /**
     * @return MediaObjectReferences
     */
    public function getMediaObjectReferences();

    /**
     * @return WorkflowStatus
     */
    public function getWorkflowStatus();

    /**
     * @return \DateTimeImmutable|null
     */
    public function getAvailableFrom();

    /**
     * @return \DateTimeImmutable
     */
    public function getAvailableTo();
}
