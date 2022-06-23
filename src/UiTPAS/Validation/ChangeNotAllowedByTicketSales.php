<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\Validation;

use Exception;

final class ChangeNotAllowedByTicketSales extends Exception
{
    public function __construct(string $eventId)
    {
        parent::__construct('Organizer change not allowed because event ' . $eventId . ' has ticket sales in UiTPAS');
    }
}
