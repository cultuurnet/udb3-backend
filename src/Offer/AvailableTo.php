<?php

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use DateTimeInterface;

class AvailableTo
{
    /**
     * @var DateTimeInterface
     */
    private $availableTo;

    /**
     * AvailableTo constructor.
     * @param DateTimeInterface $availableTo
     */
    private function __construct(DateTimeInterface $availableTo)
    {
        $this->availableTo = $availableTo;
    }

    public static function createFromCalendar(Calendar $calendar): AvailableTo
    {
        if ($calendar->getType() === CalendarType::PERMANENT()) {
            // The fixed date for a permanent calendar type does not require time information.
            return new self(new \DateTime('2100-01-01T00:00:00Z'));
        }

        /** @var DateTimeInterface $availableTo */
        $availableTo = $calendar->getEndDate();

        /**
         * https://jira.uitdatabank.be/browse/III-1581
         *
         * When available to has no time information, it needs to be set to almost midnight 23:59:59.
         *
         * To check for missing time information a check is done on formats: H:i:s
         *
         */
        if ($availableTo->format('H:i:s') === '00:00:00') {
            $availableToWithHours = new \DateTime();
            $availableToWithHours->setTimestamp($availableTo->getTimestamp());
            $availableToWithHours->add(new \DateInterval("P0000-00-00T23:59:59"));
            $availableTo = $availableToWithHours;
        }

        return new self($availableTo);
    }

    /**
     * @return DateTimeInterface
     */
    public function getAvailableTo()
    {
        return $this->availableTo;
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return $this->availableTo->format(\DateTime::ATOM);
    }
}
