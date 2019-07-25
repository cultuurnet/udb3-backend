<?php

namespace CultuurNet\UDB3\UiTPAS\Event\CommandHandling\Validation;

use CultureFeed_HttpException;
use CultuurNet\UDB3\Event\Commands\DeleteOrganizer;
use CultuurNet\UDB3\Event\Commands\UpdateOrganizer;
use CultuurNet\UDB3\Event\Commands\UpdatePriceInfo;
use CultuurNet\UDB3\Place\Commands\UpdateOrganizer as UpdatePlaceOrganizer;
use CultuurNet\UDB3\Place\Commands\UpdatePriceInfo as UpdatePlacePriceInfo;
use CultuurNet\UDB3\PriceInfo\BasePrice;
use CultuurNet\UDB3\PriceInfo\Price;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use Psr\Log\LoggerInterface;
use ValueObjects\Money\Currency;

class EventHasTicketSalesCommandValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \CultureFeed_Uitpas|\PHPUnit_Framework_MockObject_MockObject
     */
    private $uitpas;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var EventHasTicketSalesCommandValidator
     */
    private $validator;

    public function setUp()
    {
        $this->uitpas = $this->createMock(\CultureFeed_Uitpas::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->validator = new EventHasTicketSalesCommandValidator(
            $this->uitpas,
            $this->logger
        );
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_updating_the_organizer_of_an_event_with_ticket_sales()
    {
        $command = new UpdateOrganizer(
            '5e75970e-43d8-481f-88db-9a61dd087cbb',
            '596c4837-6239-47e3-bf33-2bb11dc6adc7'
        );

        $this->uitpas->expects($this->once())
            ->method('eventHasTicketSales')
            ->with('5e75970e-43d8-481f-88db-9a61dd087cbb')
            ->willReturn(true);

        $this->expectException(EventHasTicketSalesException::class);

        $this->validator->validate($command);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_deleting_the_organizer_of_an_event_with_ticket_sales()
    {
        $command = new DeleteOrganizer(
            '5e75970e-43d8-481f-88db-9a61dd087cbb',
            '596c4837-6239-47e3-bf33-2bb11dc6adc7'
        );

        $this->uitpas->expects($this->once())
            ->method('eventHasTicketSales')
            ->with('5e75970e-43d8-481f-88db-9a61dd087cbb')
            ->willReturn(true);

        $this->expectException(EventHasTicketSalesException::class);

        $this->validator->validate($command);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_updating_the_price_of_an_event_with_ticket_sales()
    {
        $command = new UpdatePriceInfo(
            '5e75970e-43d8-481f-88db-9a61dd087cbb',
            new PriceInfo(
                new BasePrice(
                    Price::fromFloat(14.99),
                    Currency::fromNative('EUR')
                )
            )
        );

        $this->uitpas->expects($this->once())
            ->method('eventHasTicketSales')
            ->with('5e75970e-43d8-481f-88db-9a61dd087cbb')
            ->willReturn(true);

        $this->expectException(EventHasTicketSalesException::class);

        $this->validator->validate($command);
    }

    /**
     * @test
     */
    public function it_should_not_throw_an_exception_when_updating_the_organizer_of_an_event_without_ticket_sales()
    {
        $command = new UpdateOrganizer(
            '5e75970e-43d8-481f-88db-9a61dd087cbb',
            '596c4837-6239-47e3-bf33-2bb11dc6adc7'
        );

        $this->uitpas->expects($this->once())
            ->method('eventHasTicketSales')
            ->with('5e75970e-43d8-481f-88db-9a61dd087cbb')
            ->willReturn(false);

        $this->validator->validate($command);
    }

    /**
     * @test
     */
    public function it_should_not_throw_an_exception_when_updating_the_price_of_an_event_without_ticket_sales()
    {
        $command = new UpdatePriceInfo(
            '5e75970e-43d8-481f-88db-9a61dd087cbb',
            new PriceInfo(
                new BasePrice(
                    Price::fromFloat(14.99),
                    Currency::fromNative('EUR')
                )
            )
        );

        $this->uitpas->expects($this->once())
            ->method('eventHasTicketSales')
            ->with('5e75970e-43d8-481f-88db-9a61dd087cbb')
            ->willReturn(false);

        $this->validator->validate($command);
    }

    /**
     * @test
     */
    public function it_should_handle_uitpas_exceptions_as_no_ticket_sales()
    {
        $command = new UpdateOrganizer(
            '5e75970e-43d8-481f-88db-9a61dd087cbb',
            '596c4837-6239-47e3-bf33-2bb11dc6adc7'
        );

        $this->uitpas->expects($this->once())
            ->method('eventHasTicketSales')
            ->with('5e75970e-43d8-481f-88db-9a61dd087cbb')
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
    public function it_should_ignore_place_commands()
    {
        $this->uitpas->expects($this->never())
            ->method('eventHasTicketSales');

        $organizerCommand = new UpdatePlaceOrganizer(
            '5e75970e-43d8-481f-88db-9a61dd087cbb',
            '596c4837-6239-47e3-bf33-2bb11dc6adc7'
        );

        $priceCommand = new UpdatePlacePriceInfo(
            '5e75970e-43d8-481f-88db-9a61dd087cbb',
            new PriceInfo(
                new BasePrice(
                    Price::fromFloat(14.99),
                    Currency::fromNative('EUR')
                )
            )
        );

        $this->validator->validate($organizerCommand);
        $this->validator->validate($priceCommand);
    }
}
