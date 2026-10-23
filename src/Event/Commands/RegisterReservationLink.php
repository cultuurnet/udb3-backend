<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;

/**
 * Looks up a reservation link at its ticket sales provider. It deliberately doesn't extend AbstractCommand: that would
 * make it an authorizable command, while it is dispatched by the system after a link was added, also when nobody with
 * edit permission is behind it (imports, CLI).
 */
final class RegisterReservationLink
{
    public function __construct(
        public readonly string $eventId,
        public readonly Url $reservationLink
    ) {
    }
}
