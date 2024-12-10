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
use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedDescription;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use DateTimeImmutable;

interface Offer
{
    public function getId(): Uuid;

    public function getMainLanguage(): Language;

    public function getTitle(): TranslatedTitle;

    public function getDescription(): ?TranslatedDescription;

    public function getCalendar(): Calendar;

    public function getTerms(): Categories;

    public function getLabels(): Labels;

    public function getOrganizerReference(): ?OrganizerReference;

    public function getAgeRange(): ?AgeRange;

    public function getPriceInfo(): ?PriceInfo;

    public function getBookingInfo(): BookingInfo;

    public function getContactPoint(): ContactPoint;

    public function getMediaObjectReferences(): MediaObjectReferences;

    public function getVideos(): VideoCollection;

    public function getWorkflowStatus(): WorkflowStatus;

    public function getAvailableFrom(): ?DateTimeImmutable;

    public function getAvailableTo(): DateTimeImmutable;
}
