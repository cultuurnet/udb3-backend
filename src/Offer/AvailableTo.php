<?php

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\EventTypeResolver;
use DateTimeImmutable;
use DateTimeInterface;

class AvailableTo
{
    /**
     * @var DateTimeInterface
     */
    private $availableTo;

    private function __construct(DateTimeInterface $availableTo)
    {
        $this->availableTo = $availableTo;
    }

    public static function createFromCalendar(Calendar $calendar, EventType $eventType = null): AvailableTo
    {
        if ($calendar->getType() === CalendarType::PERMANENT()) {
            // The fixed date for a permanent calendar type does not require time information.
            return new self(new \DateTime('2100-01-01T00:00:00Z'));
        }

        /** @var DateTimeInterface $availableTo */
        $availableTo = $calendar->getEndDate();

        if ($eventType && EventTypeResolver::isOnlyAvailableUntilStartDate($eventType)) {
            /** @var DateTimeInterface $availableTo */
            $availableTo = $calendar->getStartDate();
        }

        /**
         * https://jira.uitdatabank.be/browse/III-1581
         * When available to has no time information, it needs to be set to almost midnight 23:59:59.
         */
        if ($availableTo->format('H:i:s') === '00:00:00') {
            $availableTo = DateTimeImmutable::createFromFormat(
                'Y-m-d',
                $availableTo->format('Y-m-d')
            )->setTime(23, 59, 59);
        }

        return new self($availableTo);
    }

    public function getAvailableTo(): DateTimeInterface
    {
        return $this->availableTo;
    }

    public function __toString(): string
    {
        return $this->availableTo->format(\DateTime::ATOM);
    }
}
