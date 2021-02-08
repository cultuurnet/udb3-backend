<?php

namespace CultuurNet\UDB3\Model\Offer;

use CultuurNet\UDB3\Model\Organizer\OrganizerReference;
use CultuurNet\UDB3\Model\ValueObject\Audience\AgeRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\MediaObjectReferences;
use CultuurNet\UDB3\Model\ValueObject\Moderation\AvailableTo;
use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedDescription;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

abstract class ImmutableOffer implements Offer
{
    /**
     * @var UUID
     */
    private $id;

    /**
     * @var Language
     */
    private $mainLanguage;

    /**
     * @var TranslatedTitle
     */
    private $title;

    /**
     * @var TranslatedDescription|null
     */
    private $description;

    /**
     * @var Calendar
     */
    private $calendar;

    /**
     * @var Categories
     */
    private $categories;

    /**
     * @var Labels
     */
    private $labels;

    /**
     * @var OrganizerReference|null
     */
    private $organizerReference;

    /**
     * @var AgeRange|null
     */
    private $ageRange;

    /**
     * @var PriceInfo|null
     */
    private $priceInfo;

    /**
     * @var BookingInfo
     */
    private $bookingInfo;

    /**
     * @var ContactPoint
     */
    private $contactPoint;

    /**
     * @var MediaObjectReferences
     */
    private $mediaObjectReferences;

    /**
     * @var WorkflowStatus
     */
    private $workflowStatus;

    /**
     * @var \DateTimeImmutable|null
     */
    private $availableFrom;

    /**
     * @param UUID $id
     * @param Language $mainLanguage
     * @param TranslatedTitle $title
     * @param Calendar $calendar
     * @param Categories $categories
     */
    public function __construct(
        UUID $id,
        Language $mainLanguage,
        TranslatedTitle $title,
        Calendar $calendar,
        Categories $categories
    ) {
        $this->guardCalendarType($calendar);

        $this->id = $id;
        $this->mainLanguage = $mainLanguage;
        $this->title = $title;
        $this->calendar = $calendar;
        $this->categories = $categories;

        $this->labels = new Labels();
        $this->bookingInfo = new BookingInfo();
        $this->contactPoint = new ContactPoint();
        $this->mediaObjectReferences = new MediaObjectReferences();
        $this->workflowStatus = WorkflowStatus::DRAFT();
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getMainLanguage()
    {
        return $this->mainLanguage;
    }

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param TranslatedTitle $title
     * @return static
     */
    public function withTitle(TranslatedTitle $title)
    {
        $c = clone $this;
        $c->title = $title;
        return $c;
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param TranslatedDescription $translatedDescription
     * @return static
     */
    public function withDescription(TranslatedDescription $translatedDescription)
    {
        $c = clone $this;
        $c->description = $translatedDescription;
        return $c;
    }

    /**
     * @return static
     */
    public function withoutDescription()
    {
        $c = clone $this;
        $c->description = null;
        return $c;
    }

    /**
     * @inheritdoc
     */
    public function getCalendar()
    {
        return $this->calendar;
    }

    /**
     * @param Calendar $calendar
     * @return static
     */
    public function withCalendar(Calendar $calendar)
    {
        $this->guardCalendarType($calendar);

        $c = clone $this;
        $c->calendar = $calendar;
        return $c;
    }

    /**
     * @return Categories
     */
    public function getTerms()
    {
        return $this->categories;
    }

    /**
     * @param Categories $categories
     * @return static
     */
    public function withTerms(Categories $categories)
    {
        $c = clone $this;
        $c->categories = $categories;
        return $c;
    }

    /**
     * @return Labels
     */
    public function getLabels()
    {
        return $this->labels;
    }

    /**
     * @param Labels $labels
     * @return static
     */
    public function withLabels(Labels $labels)
    {
        $c = clone $this;
        $c->labels = $labels;
        return $c;
    }

    /**
     * @return OrganizerReference|null
     */
    public function getOrganizerReference()
    {
        return $this->organizerReference;
    }

    /**
     * @param OrganizerReference $organizerReference
     * @return static
     */
    public function withOrganizerReference(OrganizerReference $organizerReference)
    {
        $c = clone $this;
        $c->organizerReference = $organizerReference;
        return $c;
    }

    /**
     * @return static
     */
    public function withoutOrganizerReference()
    {
        $c = clone $this;
        $c->organizerReference = null;
        return $c;
    }

    /**
     * @return AgeRange|null
     */
    public function getAgeRange()
    {
        return $this->ageRange;
    }

    /**
     * @param AgeRange $ageRange
     * @return static
     */
    public function withAgeRange(AgeRange $ageRange)
    {
        $c = clone $this;
        $c->ageRange = $ageRange;
        return $c;
    }

    /**
     * @return static
     */
    public function withoutAgeRange()
    {
        $c = clone $this;
        $c->ageRange = null;
        return $c;
    }

    /**
     * @inheritdoc
     */
    public function getPriceInfo()
    {
        return $this->priceInfo;
    }

    /**
     * @param PriceInfo $priceInfo
     * @return static
     */
    public function withPriceInfo(PriceInfo $priceInfo)
    {
        $c = clone $this;
        $c->priceInfo = $priceInfo;
        return $c;
    }

    /**
     * @return static
     */
    public function withoutPriceInfo()
    {
        $c = clone $this;
        $c->priceInfo = null;
        return $c;
    }

    /**
     * @return BookingInfo
     */
    public function getBookingInfo()
    {
        return $this->bookingInfo;
    }

    /**
     * @param BookingInfo $bookingInfo
     * @return static
     */
    public function withBookingInfo(BookingInfo $bookingInfo)
    {
        $c = clone $this;
        $c->bookingInfo = $bookingInfo;
        return $c;
    }

    /**
     * @return ContactPoint
     */
    public function getContactPoint()
    {
        return $this->contactPoint;
    }

    /**
     * @param ContactPoint $contactPoint
     * @return static
     */
    public function withContactPoint(ContactPoint $contactPoint)
    {
        $c = clone $this;
        $c->contactPoint = $contactPoint;
        return $c;
    }

    /**
     * @return MediaObjectReferences
     */
    public function getMediaObjectReferences()
    {
        return $this->mediaObjectReferences;
    }

    /**
     * @param MediaObjectReferences $mediaObjectReferences
     * @return static
     */
    public function withMediaObjectReferences(MediaObjectReferences $mediaObjectReferences)
    {
        $c = clone $this;
        $c->mediaObjectReferences = $mediaObjectReferences;
        return $c;
    }

    /**
     * @return WorkflowStatus
     */
    public function getWorkflowStatus()
    {
        return $this->workflowStatus;
    }

    /**
     * @param WorkflowStatus $workflowStatus
     * @return static
     */
    public function withWorkflowStatus(WorkflowStatus $workflowStatus)
    {
        $c = clone $this;
        $c->workflowStatus = $workflowStatus;
        return $c;
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function getAvailableFrom()
    {
        return $this->availableFrom;
    }

    /**
     * @param \DateTimeImmutable $availableFrom
     * @return static
     */
    public function withAvailableFrom(\DateTimeImmutable $availableFrom)
    {
        $c = clone $this;
        $c->availableFrom = $availableFrom;
        return $c;
    }

    /**
     * @return static
     */
    public function withoutAvailableFrom()
    {
        $c = clone $this;
        $c->availableFrom = null;
        return $c;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getAvailableTo()
    {
        return AvailableTo::createFromCalendar($this->calendar);
    }

    /**
     * Some offers, eg. place, only allow some specific calendar types.
     * While they could enforce the calendar type in their constructor,
     * they can't enforce it via ImmutableOffer::withCalendar() because of the
     * Liskov substitution principle, so we provide an abstract method that will
     * be called wherever a calendar is injected.
     *
     * @param Calendar $calendar
     * @throws \InvalidArgumentException
     */
    abstract protected function guardCalendarType(Calendar $calendar);
}
