<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

final class SubEvent
{
    /**
     * @var DateRange
     */
    private $dateRange;

    /**
     * @var Status
     */
    private $status;

    public function __construct(DateRange $dateRange, Status $status)
    {
        $this->dateRange = $dateRange;
        $this->status = $status;
    }

    public function getDateRange(): DateRange
    {
        return $this->dateRange;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }
}
