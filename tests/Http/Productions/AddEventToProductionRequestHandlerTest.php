<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Productions;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Productions\AddEventToProduction;
use CultuurNet\UDB3\Event\Productions\ProductionId;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

final class AddEventToProductionRequestHandlerTest extends TestCase
{
    private TraceableCommandBus $commandBus;

    private AddEventToProductionRequestHandler $addEventToProductionRequestHandler;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->addEventToProductionRequestHandler = new AddEventToProductionRequestHandler($this->commandBus);
    }

    /**
     * @test
     */
    public function it_can_add_an_event_to_an_existing_production(): void
    {
        $productionId = ProductionId::generate();
        $eventId = UUID::uuid4()->toString();

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('productionId', $productionId->toNative())
            ->withRouteParameter('eventId', $eventId)
            ->build('POST');

        $this->commandBus->record();

        $response = $this->addEventToProductionRequestHandler->handle($request);

        $this->assertEquals(
            [new AddEventToProduction($eventId, $productionId)],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertEquals(204, $response->getStatusCode());
    }
}
