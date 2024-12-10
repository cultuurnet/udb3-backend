<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Event;

use CultuurNet\UDB3\Model\Offer\ImmutableOffer;
use CultuurNet\UDB3\Model\Place\PlaceReference;
use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Online\AttendanceMode;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use InvalidArgumentException;

class ImmutableEvent extends ImmutableOffer implements Event
{
    private PlaceReference $placeReference;

    private AttendanceMode $attendanceMode;

    private ?Url $onlineUrl = null;

    private AudienceType $audience;

    public function __construct(
        UUID $id,
        Language $mainLanguage,
        TranslatedTitle $title,
        Calendar $calendar,
        PlaceReference $placeReference,
        Categories $categories
    ) {
        // Do not enforce this on Categories class itself because it can cause
        // problems with other models, eg. Place (because dummy locations have
        // no categories).
        // We can not enforce the exact requirement that "eventtype" is required
        // because categories can be POSTed using only their id.
        if ($categories->isEmpty()) {
            throw new InvalidArgumentException('Categories should not be empty (eventtype required).');
        }

        parent::__construct($id, $mainLanguage, $title, $calendar, $categories);
        $this->placeReference = $placeReference;
        $this->attendanceMode = AttendanceMode::offline();
        $this->audience = AudienceType::everyone();
    }

    public function getPlaceReference(): PlaceReference
    {
        return $this->placeReference;
    }

    public function withPlaceReference(PlaceReference $placeReference): ImmutableEvent
    {
        $c = clone $this;
        $c->placeReference = $placeReference;
        return $c;
    }

    public function getAttendanceMode(): AttendanceMode
    {
        return $this->attendanceMode;
    }

    public function withAttendanceMode(AttendanceMode $attendanceMode): ImmutableEvent
    {
        $c = clone $this;
        $c->attendanceMode = $attendanceMode;
        return $c;
    }

    public function getOnlineUrl(): ?Url
    {
        return $this->onlineUrl;
    }

    public function withOnlineUrl(Url $onlineUrl): ImmutableEvent
    {
        $c = clone $this;
        $c->onlineUrl = $onlineUrl;
        return $c;
    }

    public function getAudienceType(): AudienceType
    {
        return $this->audience;
    }

    public function withAudienceType(AudienceType $audience): ImmutableEvent
    {
        $c = clone $this;
        $c->audience = $audience;
        return $c;
    }

    protected function guardCalendarType(Calendar $calendar): void
    {
        // Any calendar is fine for events.
    }
}
