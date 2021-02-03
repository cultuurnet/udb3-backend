<?php

namespace CultuurNet\UDB3\UiTPAS\Event\CommandHandling\Validation;

use CultuurNet\UDB3\Broadway\CommandHandling\Validation\CommandValidatorInterface;
use CultuurNet\UDB3\Event\Commands\DeleteOrganizer;
use CultuurNet\UDB3\Event\Commands\UpdateOrganizer;
use CultuurNet\UDB3\Event\Commands\UpdatePriceInfo;
use Psr\Log\LoggerInterface;

class EventHasTicketSalesCommandValidator implements CommandValidatorInterface
{
    /**
     * @var \CultureFeed_Uitpas
     */
    private $uitpas;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        \CultureFeed_Uitpas $uitpas,
        LoggerInterface $logger
    ) {
        $this->uitpas = $uitpas;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function validate($command)
    {
        if (!($command instanceof UpdateOrganizer) &&
            !($command instanceof UpdatePriceInfo) &&
            !($command instanceof DeleteOrganizer)) {
            return;
        }

        $eventId = $command->getItemId();

        try {
            $hasTicketSales = $this->uitpas->eventHasTicketSales($eventId);
        } catch (\Exception $exception) {
            // By design to catch all exceptions and map an exception to no ticket sales.
            // This is done to allow setting price and organizer even when UiTPAS has issues.
            // All exceptions will be logged.
            $this->logger->warning(
                'Ticket call sales failed with exception message "'
                . $exception->getMessage() . '" and exception code "' . $exception->getCode() . '". '
                . 'Assuming no ticket sales for event ' . $eventId
            );

            return;
        }

        if ($hasTicketSales) {
            throw new EventHasTicketSalesException($eventId);
        }
    }
}
