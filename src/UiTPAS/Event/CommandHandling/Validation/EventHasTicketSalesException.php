<?php

namespace CultuurNet\UDB3\UiTPAS\Event\CommandHandling\Validation;

class EventHasTicketSalesException extends \Exception
{
    public function __construct($eventId)
    {
        $message = sprintf('Event %s has already had ticket sales in UiTPAS.', $eventId);
        parent::__construct($message);
    }
}
