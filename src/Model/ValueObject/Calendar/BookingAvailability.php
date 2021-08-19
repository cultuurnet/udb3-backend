<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

final class BookingAvailability
{
    private BookingAvailability $type;

    public function __construct(BookingAvailability $type)
    {
        $this->type = $type;
    }

    public function getType(): BookingAvailability
    {
        return $this->type;
    }
}
