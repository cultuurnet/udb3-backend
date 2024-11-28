<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;

abstract class AbstractUpdateBookingInfo extends AbstractCommand
{
    protected BookingInfo $bookingInfo;

    public function __construct(string $itemId, BookingInfo $bookingInfo)
    {
        parent::__construct($itemId);
        $this->bookingInfo = $bookingInfo;
    }

    public function getBookingInfo(): BookingInfo
    {
        return $this->bookingInfo;
    }
}
