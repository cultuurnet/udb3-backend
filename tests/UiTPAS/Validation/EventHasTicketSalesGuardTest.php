<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\Validation;

use Broadway\Repository\AggregateNotFoundException;
use CultureFeed_HttpException;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Offer\Commands\DeleteOrganizer;
use CultuurNet\UDB3\Offer\Commands\UpdateOrganizer;
use CultuurNet\UDB3\Offer\Offer;
use Error;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class EventHasTicketSalesGuardTest extends TestCase
{
    private \CultureFeed_Uitpas&MockObject $uitpas;

    private LoggerInterface&MockObject $logger;

    private Offer&MockObject $event;

    private string $placeId;

    private string $eventId;

    private EventHasTicketSalesGuard $eventHasTicketSalesGuard;

    public function setUp(): void
    {
        $this->uitpas = $this->createMock(\CultureFeed_Uitpas::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $eventRepository = $this->createMock(EventRepository::class);
        $this->event = $this->createMock(Offer::class);

        $this->eventId = '5e75970e-43d8-481f-88db-9a61dd087cbb';
        $this->placeId = '9a129c08-1b16-46d6-a4b7-9ffc6d0741fe';

        $eventRepository->method('load')
            ->willReturnCallback(
                function (string $offerId) {
                    if ($offerId === $this->placeId) {
                        throw new AggregateNotFoundException();
                    }
                    return $this->event;
                }
            );

        $this->eventHasTicketSalesGuard = new EventHasTicketSalesGuard(
            $this->uitpas,
            $eventRepository,
            $this->logger
        );
    }

    /**
     * @test
     */
    public function it_allows_updating_the_organizer_of_an_event_without_ticket_sales(): void
    {
        $command = new UpdateOrganizer(
            $this->eventId,
            '596c4837-6239-47e3-bf33-2bb11dc6adc7'
        );

        $this->uitpas->expects($this->once())
            ->method('eventHasTicketSales')
            ->with($this->eventId)
            ->willReturn(false);

        $this->eventHasTicketSalesGuard->guard($command);
    }

    /**
     * @test
     */
    public function it_allows_deleting_the_organizer_of_an_event_without_ticket_sales(): void
    {
        $command = new DeleteOrganizer(
            $this->eventId,
            '596c4837-6239-47e3-bf33-2bb11dc6adc7'
        );

        $this->uitpas->expects($this->once())
            ->method('eventHasTicketSales')
            ->with($this->eventId)
            ->willReturn(false);

        $this->eventHasTicketSalesGuard->guard($command);
    }

    /**
     * @test
     */
    public function it_throws_when_updating_the_organizer_of_an_event_with_ticket_sales(): void
    {
        $command = new UpdateOrganizer(
            $this->eventId,
            '596c4837-6239-47e3-bf33-2bb11dc6adc7'
        );

        $this->uitpas->expects($this->once())
            ->method('eventHasTicketSales')
            ->with($this->eventId)
            ->willReturn(true);

        $this->expectException(ChangeNotAllowedByTicketSales::class);

        $this->eventHasTicketSalesGuard->guard($command);
    }

    /**
     * @test
     */
    public function it_throws_when_deleting_the_organizer_of_an_event_with_ticket_sales(): void
    {
        $command = new DeleteOrganizer(
            $this->eventId,
            '596c4837-6239-47e3-bf33-2bb11dc6adc7'
        );

        $this->uitpas->expects($this->once())
            ->method('eventHasTicketSales')
            ->with($this->eventId)
            ->willReturn(true);

        $this->expectException(ChangeNotAllowedByTicketSales::class);

        $this->eventHasTicketSalesGuard->guard($command);
    }

    /**
     * @test
     */
    public function it_handles_uitpas_exceptions_as_no_ticket_sales(): void
    {
        $command = new UpdateOrganizer(
            $this->eventId,
            '596c4837-6239-47e3-bf33-2bb11dc6adc7'
        );

        $this->uitpas->expects($this->once())
            ->method('eventHasTicketSales')
            ->with($this->eventId)
            ->willThrowException(new CultureFeed_HttpException('result message', 404));

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Ticket call sales failed with exception message'
                . ' "The reponse for the HTTP request was not 200. result message" and exception code "404".'
                . ' Assuming no ticket sales for event 5e75970e-43d8-481f-88db-9a61dd087cbb');

        $this->eventHasTicketSalesGuard->guard($command);
    }

    /**
     * @test
     */
    public function it_handles_error_exception_as_no_ticket_sales(): void
    {
        $command = new UpdateOrganizer(
            $this->eventId,
            '596c4837-6239-47e3-bf33-2bb11dc6adc7'
        );

        $this->uitpas->expects($this->once())
            ->method('eventHasTicketSales')
            ->with($this->eventId)
            ->willThrowException(new Error('parser error'));

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Ticket call sales failed with exception message'
                . ' "parser error" and exception code "0".'
                . ' Assuming no ticket sales for event 5e75970e-43d8-481f-88db-9a61dd087cbb');

        $this->eventHasTicketSalesGuard->guard($command);
    }

    /**
     * @test
     */
    public function it_ignores_commands_on_place(): void
    {
        $this->uitpas->expects($this->never())
            ->method('eventHasTicketSales');

        $organizerCommand = new UpdateOrganizer(
            $this->placeId,
            '596c4837-6239-47e3-bf33-2bb11dc6adc7'
        );

        $this->eventHasTicketSalesGuard->guard($organizerCommand);
    }
}
