<?php

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Offer\Events\AbstractBookingInfoUpdated;

final class BookingInfoUpdated extends AbstractBookingInfoUpdated
{
    use BackwardsCompatibleEventTrait;
}
