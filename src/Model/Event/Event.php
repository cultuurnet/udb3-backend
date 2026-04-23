<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Event;

use CultuurNet\UDB3\Model\Offer\Offer;
use CultuurNet\UDB3\Model\Place\PlaceReference;
use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use CultuurNet\UDB3\Model\ValueObject\Audience\BirthdateRange;
use CultuurNet\UDB3\Model\ValueObject\Faq\Faqs;
use CultuurNet\UDB3\Model\ValueObject\Online\AttendanceMode;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;

interface Event extends Offer
{
    public function getAttendanceMode(): AttendanceMode;

    public function getOnlineUrl(): ?Url;

    public function getAudienceType(): AudienceType;

    public function getPlaceReference(): PlaceReference;

    public function getFaq(): Faqs;

    public function getDeparturePlaces(): Urls;

    public function getBirthdateRange(): ?BirthdateRange;
}
