<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\Validation;

use Broadway\Repository\AggregateNotFoundException;
use CultureFeed_Uitpas;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;
use Psr\Log\LoggerInterface;

final class EventHasTicketSalesGuard
{
    private CultureFeed_Uitpas $uitpas;

    private EventRepository $eventRepository;

    private LoggerInterface $logger;

    public function __construct(
        CultureFeed_Uitpas $uitpas,
        EventRepository $eventRepository,
        LoggerInterface $logger
    ) {
        $this->uitpas = $uitpas;
        $this->eventRepository = $eventRepository;
        $this->logger = $logger;
    }

    public function guard(AbstractCommand $command): void
    {
        $eventId = $command->getItemId();

        try {
            $this->eventRepository->load($eventId);
        } catch (AggregateNotFoundException $exception) {
            // The command is for a place which can not have ticket sales.
            return;
        }

        try {
            $hasTicketSales = $this->uitpas->eventHasTicketSales($eventId);
        } catch (\Throwable $exception) {
            // By design to catch all exceptions and map an exception to no ticket sales.
            // This is done to allow changing an organizer even when UiTPAS has issues.
            // All exceptions will be logged.
            $this->logger->warning(
                'Ticket call sales failed with exception message "'
                . $exception->getMessage() . '" and exception code "' . $exception->getCode() . '". '
                . 'Assuming no ticket sales for event ' . $eventId
            );

            return;
        }

        if ($hasTicketSales) {
            throw new ChangeNotAllowedByTicketSales($eventId);
        }
    }
}
