<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Productions;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Productions\ProductionId;
use CultuurNet\UDB3\Event\Productions\RemoveEventFromProduction;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

final class RemoveEventFromProductionRequestHandlerTest extends TestCase
{
    private TraceableCommandBus $commandBus;

    private RemoveEventFromProductionRequestHandler $removeEventFromProductionRequestHandler;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->removeEventFromProductionRequestHandler = new RemoveEventFromProductionRequestHandler($this->commandBus);
    }

    /**
     * @test
     */
    public function it_can_remove_an_event_from_a_production(): void
    {
        $productionId = ProductionId::generate();
        $eventId = UUID::uuid4()->toString();

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('productionId', $productionId->toNative())
            ->withRouteParameter('eventId', $eventId)
            ->build('DELETE');

        $this->commandBus->record();

        $response = $this->removeEventFromProductionRequestHandler->handle($request);

        $this->assertEquals(
            [new RemoveEventFromProduction($eventId, $productionId)],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertEquals(204, $response->getStatusCode());
    }
}
