<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ticketing\Repositories;

use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

final class TicketingRegistrations extends Collection
{
    public function __construct(TicketingRegistration ...$registrations)
    {
        parent::__construct(...$registrations);
    }
}
