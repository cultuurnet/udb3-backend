<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\Event\CommandHandling\Validation;

use Broadway\Domain\AggregateRoot;
use Broadway\Repository\AggregateNotFoundException;
use CultureFeed_HttpException;
use CultuurNet\UDB3\Event\Commands\DeleteOrganizer;
use CultuurNet\UDB3\Event\Commands\UpdateOrganizer;
use CultuurNet\UDB3\Event\Recommendations\Recommendation;
use CultuurNet\UDB3\Event\Recommendations\Recommendations;
use CultuurNet\UDB3\Offer\Commands\UpdatePriceInfo;
use CultuurNet\UDB3\Place\Commands\UpdateOrganizer as UpdatePlaceOrganizer;
use CultuurNet\UDB3\Place\PlaceRepository;
use CultuurNet\UDB3\PriceInfo\BasePrice;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class EventHasTicketSalesCommandValidatorTest extends TestCase
{
    /**
     * @var \CultureFeed_Uitpas|MockObject
     */
    private $uitpas;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    private EventHasTicketSalesCommandValidator $validator;

    private string $placeId;

    private string $eventId;

    public function setUp(): void
    {
        $this->uitpas = $this->createMock(\CultureFeed_Uitpas::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $placeRepository = $this->createMock(PlaceRepository::class);

        $this->eventId = '5e75970e-43d8-481f-88db-9a61dd087cbb';
        $this->placeId = '9a129c08-1b16-46d6-a4b7-9ffc6d0741fe';

        $placeRepository->method('load')
            ->willReturnCallback(
                function (string $offerId) {
                    if ($offerId === $this->placeId) {
                        return $this->createMock(AggregateRoot::class);
                    }
                    throw new AggregateNotFoundException();
                }
            );

        $this->validator = new EventHasTicketSalesCommandValidator(
            $this->uitpas,
            $this->logger,
            $placeRepository
        );
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_updating_the_organizer_of_an_event_with_ticket_sales(): void
    {
        $command = new UpdateOrganizer(
            $this->eventId,
            '596c4837-6239-47e3-bf33-2bb11dc6adc7'
        );

        $this->uitpas->expects($this->once())
            ->method('eventHasTicketSales')
            ->with($this->eventId)
            ->willReturn(true);

        $this->expectException(EventHasTicketSalesException::class);

        $this->validator->validate($command);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_deleting_the_organizer_of_an_event_with_ticket_sales(): void
    {
        $command = new DeleteOrganizer(
            $this->eventId,
            '596c4837-6239-47e3-bf33-2bb11dc6adc7'
        );

        $this->uitpas->expects($this->once())
            ->method('eventHasTicketSales')
            ->with($this->eventId)
            ->willReturn(true);

        $this->expectException(EventHasTicketSalesException::class);

        $this->validator->validate($command);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_updating_the_price_of_an_event_with_ticket_sales(): void
    {
        $command = new UpdatePriceInfo(
            $this->eventId,
            new PriceInfo(
                new BasePrice(
                    new Money(1499, new Currency('EUR'))
                )
            )
        );

        $this->uitpas->expects($this->once())
            ->method('eventHasTicketSales')
            ->with($this->eventId)
            ->willReturn(true);

        $this->expectException(EventHasTicketSalesException::class);

        $this->validator->validate($command);
    }

    /**
     * @test
     */
    public function it_should_not_throw_an_exception_when_updating_the_organizer_of_an_event_without_ticket_sales(): void
    {
        $command = new UpdateOrganizer(
            $this->eventId,
            '596c4837-6239-47e3-bf33-2bb11dc6adc7'
        );

        $this->uitpas->expects($this->once())
            ->method('eventHasTicketSales')
            ->with($this->eventId)
            ->willReturn(false);

        $this->validator->validate($command);
    }

    /**
     * @test
     */
    public function it_should_not_throw_an_exception_when_updating_the_price_of_an_event_without_ticket_sales(): void
    {
        $command = new UpdatePriceInfo(
            $this->eventId,
            new PriceInfo(
                new BasePrice(
                    new Money(1499, new Currency('EUR'))
                )
            )
        );

        $this->uitpas->expects($this->once())
            ->method('eventHasTicketSales')
            ->with($this->eventId)
            ->willReturn(false);

        $this->validator->validate($command);
    }

    /**
     * @test
     */
    public function it_should_handle_uitpas_exceptions_as_no_ticket_sales(): void
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

        $this->validator->validate($command);
    }

    /**
     * @test
     */
    public function it_should_ignore_place_commands(): void
    {
        $this->uitpas->expects($this->never())
            ->method('eventHasTicketSales');

        $organizerCommand = new UpdatePlaceOrganizer(
            $this->placeId,
            '596c4837-6239-47e3-bf33-2bb11dc6adc7'
        );

        $priceCommand = new UpdatePriceInfo(
            $this->placeId,
            new PriceInfo(
                new BasePrice(
                    new Money(1499, new Currency('EUR'))
                )
            )
        );

        $this->validator->validate($organizerCommand);
        $this->validator->validate($priceCommand);
    }
}
