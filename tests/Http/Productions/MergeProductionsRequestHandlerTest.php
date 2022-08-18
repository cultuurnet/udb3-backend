<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Productions;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Productions\MergeProductions;
use CultuurNet\UDB3\Event\Productions\ProductionId;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\TestCase;

final class MergeProductionsRequestHandlerTest extends TestCase
{
    private TraceableCommandBus $commandBus;

    private MergeProductionsRequestHandler $mergeProductionsRequestHandler;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->mergeProductionsRequestHandler = new MergeProductionsRequestHandler($this->commandBus);
    }

    /**
     * @test
     */
    public function it_can_add_an_event_to_an_existing_production(): void
    {
        $productionId = ProductionId::generate();
        $fromProductionId = ProductionId::generate();

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('productionId', $productionId->toNative())
            ->withRouteParameter('fromProductionId', $fromProductionId->toNative())
            ->build('POST');

        $this->commandBus->record();

        $response = $this->mergeProductionsRequestHandler->handle($request);

        $this->assertEquals(
            [new MergeProductions($fromProductionId, $productionId)],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertEquals(204, $response->getStatusCode());
    }
}
