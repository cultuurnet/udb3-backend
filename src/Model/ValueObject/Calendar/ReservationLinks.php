<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

/**
 * The distinct reservation links on the subEvents of a calendar.
 */
final class ReservationLinks extends Collection
{
    public function __construct(Url ...$links)
    {
        parent::__construct(...$links);
    }

    public static function fromCalendar(?Calendar $calendar): self
    {
        $links = new self();

        if (!$calendar instanceof CalendarWithSubEvents) {
            return $links;
        }

        foreach ($calendar->getSubEvents() as $subEvent) {
            $bookingInfo = $subEvent->getBookingInfo();
            if ($bookingInfo !== null && !$links->contains($bookingInfo->getUrl())) {
                $links = $links->with($bookingInfo->getUrl());
            }
        }

        return $links;
    }

    /**
     * The links of this collection that are not in the other one.
     */
    public function notIn(ReservationLinks $other): self
    {
        return $this->filter(fn (Url $link) => !$other->contains($link));
    }
}
