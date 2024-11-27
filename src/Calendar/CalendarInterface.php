<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use DateTimeInterface;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar instead where possible.
 */
interface CalendarInterface
{
    public function getType(): CalendarType;

    public function getStartDate(): ?DateTimeInterface;

    public function getEndDate(): ?DateTimeInterface;

    public function getOpeningHours(): array;

    /**
     * @return SubEvent[]
     */
    public function getSubEvents(): array;
}
