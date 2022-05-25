<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\Event\CommandHandling\Validation;

use Broadway\Repository\AggregateNotFoundException;
use CultureFeed_Uitpas;
use CultuurNet\UDB3\Broadway\CommandHandling\Validation\CommandValidatorInterface;
use CultuurNet\UDB3\Event\Commands\DeleteOrganizer;
use CultuurNet\UDB3\Event\Commands\UpdateOrganizer;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Offer\Commands\UpdatePriceInfo;
use Psr\Log\LoggerInterface;

class EventHasTicketSalesCommandValidator implements CommandValidatorInterface
{
    private CultureFeed_Uitpas $uitpas;

    private LoggerInterface $logger;

    private EventRepository $eventRepository;

    public function __construct(
        CultureFeed_Uitpas $uitpas,
        EventRepository $eventRepository,
        LoggerInterface $logger
    ) {
        $this->uitpas = $uitpas;
        $this->eventRepository = $eventRepository;
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
            $this->eventRepository->load($eventId);
        } catch (AggregateNotFoundException $exception) {
            // The command is for a place which can not have ticket sales.
            return;
        }

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
