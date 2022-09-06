<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Productions;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Event\Productions\ProductionId;
use CultuurNet\UDB3\Event\Productions\RenameProduction;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\TestCase;

final class RenameProductionRequestHandlerTest extends TestCase
{
    private TraceableCommandBus $commandBus;

    private RenameProductionRequestHandler $renameProductionRequestHandler;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->renameProductionRequestHandler = new RenameProductionRequestHandler(
            $this->commandBus,
            new RenameProductionValidator()
        );
    }

    /**
     * @test
     */
    public function it_can_rename_a_production(): void
    {
        $productionId = ProductionId::generate();
        $name = 'renamed production';

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('productionId', $productionId->toNative())
            ->withJsonBodyFromArray(['name' => $name])
            ->build('POST');

        $this->commandBus->record();

        $response = $this->renameProductionRequestHandler->handle($request);

        $this->assertEquals(
            [new RenameProduction($productionId, $name)],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_prevents_empty_rename(): void
    {
        $productionId = ProductionId::generate();

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('productionId', $productionId->toNative())
            ->withJsonBodyFromArray(['name' => ''])
            ->build('POST');

        $this->expectException(DataValidationException::class);

        $this->renameProductionRequestHandler->handle($request);
    }
}
