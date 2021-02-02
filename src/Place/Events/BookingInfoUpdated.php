<?php

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\Offer\Events\AbstractBookingInfoUpdated;

final class BookingInfoUpdated extends AbstractBookingInfoUpdated
{
    use BackwardsCompatibleEventTrait;
}
