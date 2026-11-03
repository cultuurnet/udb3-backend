<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ticketing\Providers;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use Exception;

/**
 * A ticket sales provider that knows the events behind its reservation links.
 */
interface TicketingProvider
{
    /**
     * @throws ReservationLinkNotFound when the provider has no open event behind the link
     * @throws InvalidReservationLink when the link is not a reservation link of the provider
     * @throws Exception when the provider can't be reached or answers in an unexpected way
     */
    public function getBookingInfo(Url $reservationLink): ReservationLinkBookingInfo;
}
