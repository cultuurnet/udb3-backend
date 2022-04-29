<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Event;

use CultuurNet\UDB3\Model\Offer\Offer;
use CultuurNet\UDB3\Model\Place\PlaceReference;
use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use CultuurNet\UDB3\Model\ValueObject\Virtual\AttendanceMode;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

interface Event extends Offer
{
    public function getAttendanceMode(): AttendanceMode;

    public function getOnlineUrl(): Url;

    public function getAudienceType(): AudienceType;

    public function getPlaceReference(): PlaceReference;
}
