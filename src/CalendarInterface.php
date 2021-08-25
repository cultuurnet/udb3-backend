<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Calendar\OpeningHour;
use DateTimeInterface;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar instead where possible.
 */
interface CalendarInterface
{
    /**
     * Get current calendar type.
     *
     * @return CalendarType
     */
    public function getType();

    /**
     * Get the start date.
     *
     * @return DateTimeInterface
     */
    public function getStartDate();

    /**
     * Get the end date.
     *
     * @return DateTimeInterface
     */
    public function getEndDate();

    /**
     * Get the opening hours.
     *
     * @return OpeningHour[]
     */
    public function getOpeningHours();

    /**
     * Get timestamps.
     *
     * @return Timestamp[]
     */
    public function getTimestamps();
}
