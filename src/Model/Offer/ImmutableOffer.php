<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Offer;

use CultuurNet\UDB3\Model\Organizer\OrganizerReference;
use CultuurNet\UDB3\Model\ValueObject\Audience\AgeRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\MediaObjectReferences;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\VideoCollection;
use CultuurNet\UDB3\Model\ValueObject\Moderation\AvailableTo;
use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedDescription;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use DateTimeImmutable;

abstract class ImmutableOffer implements Offer
{
    private Uuid $id;

    private Language $mainLanguage;

    private TranslatedTitle $title;

    private ?TranslatedDescription $description = null;

    private Calendar $calendar;

    private Categories $categories;

    private Labels $labels;

    private ?OrganizerReference $organizerReference = null;

    private ?AgeRange $ageRange = null;

    private ?PriceInfo $priceInfo = null;

    private BookingInfo $bookingInfo;

    private ContactPoint $contactPoint;

    private MediaObjectReferences $mediaObjectReferences;

    private VideoCollection $videos;

    private WorkflowStatus $workflowStatus;

    private ?DateTimeImmutable $availableFrom = null;

    public function __construct(
        Uuid $id,
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
        $this->videos = new VideoCollection();
        $this->workflowStatus = WorkflowStatus::DRAFT();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getMainLanguage(): Language
    {
        return $this->mainLanguage;
    }

    public function getTitle(): TranslatedTitle
    {
        return $this->title;
    }

    /**
     * @return static
     */
    public function withTitle(TranslatedTitle $title)
    {
        $c = clone $this;
        $c->title = $title;
        return $c;
    }

    public function getDescription(): ?TranslatedDescription
    {
        return $this->description;
    }

    /**
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

    public function getCalendar(): Calendar
    {
        return $this->calendar;
    }

    /**
     * @return static
     */
    public function withCalendar(Calendar $calendar)
    {
        $this->guardCalendarType($calendar);

        $c = clone $this;
        $c->calendar = $calendar;
        return $c;
    }

    public function getTerms(): Categories
    {
        return $this->categories;
    }

    /**
     * @return static
     */
    public function withTerms(Categories $categories)
    {
        $c = clone $this;
        $c->categories = $categories;
        return $c;
    }

    public function getLabels(): Labels
    {
        return $this->labels;
    }

    /**
     * @return static
     */
    public function withLabels(Labels $labels)
    {
        $c = clone $this;
        $c->labels = $labels;
        return $c;
    }

    public function getOrganizerReference(): ?OrganizerReference
    {
        return $this->organizerReference;
    }

    /**
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

    public function getAgeRange(): ?AgeRange
    {
        return $this->ageRange;
    }

    /**
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

    public function getPriceInfo(): ?PriceInfo
    {
        return $this->priceInfo;
    }

    /**
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

    public function getBookingInfo(): BookingInfo
    {
        return $this->bookingInfo;
    }

    /**
     * @return static
     */
    public function withBookingInfo(BookingInfo $bookingInfo)
    {
        $c = clone $this;
        $c->bookingInfo = $bookingInfo;
        return $c;
    }

    public function getContactPoint(): ContactPoint
    {
        return $this->contactPoint;
    }

    /**
     * @return static
     */
    public function withContactPoint(ContactPoint $contactPoint)
    {
        $c = clone $this;
        $c->contactPoint = $contactPoint;
        return $c;
    }

    public function getMediaObjectReferences(): MediaObjectReferences
    {
        return $this->mediaObjectReferences;
    }

    /**
     * @return static
     */
    public function withMediaObjectReferences(MediaObjectReferences $mediaObjectReferences)
    {
        $c = clone $this;
        $c->mediaObjectReferences = $mediaObjectReferences;
        return $c;
    }

    public function getVideos(): VideoCollection
    {
        return $this->videos;
    }

    public function withVideos(VideoCollection $videos): ImmutableOffer
    {
        $clone = clone $this;
        $clone->videos = $videos;
        return $clone;
    }

    public function getWorkflowStatus(): WorkflowStatus
    {
        return $this->workflowStatus;
    }

    /**
     * @return static
     */
    public function withWorkflowStatus(WorkflowStatus $workflowStatus)
    {
        $c = clone $this;
        $c->workflowStatus = $workflowStatus;
        return $c;
    }

    public function getAvailableFrom(): ?DateTimeImmutable
    {
        return $this->availableFrom;
    }

    /**
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

    public function getAvailableTo(): DateTimeImmutable
    {
        return AvailableTo::createFromCalendar($this->calendar, null);
    }

    /**
     * Some offers, eg. place, only allow some specific calendar types.
     * While they could enforce the calendar type in their constructor,
     * they can't enforce it via ImmutableOffer::withCalendar() because of the
     * Liskov substitution principle, so we provide an abstract method that will
     * be called wherever a calendar is injected.
     *
     * @throws \InvalidArgumentException
     */
    abstract protected function guardCalendarType(Calendar $calendar): void;
}
