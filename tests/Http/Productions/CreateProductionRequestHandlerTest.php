<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Productions;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Productions\GroupEventsAsProduction;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class CreateProductionRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private TraceableCommandBus $commandBus;

    private CreateProductionRequestHandler $createProductionRequestHandler;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->createProductionRequestHandler = new CreateProductionRequestHandler(
            $this->commandBus,
            new CreateProductionValidator()
        );
    }

    /**
     * @test
     */
    public function it_can_create_a_production(): void
    {
        $eventId1 = Uuid::uuid4()->toString();
        $eventId2 = Uuid::uuid4()->toString();
        $name = 'Singing in the drain';

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray(
                [
                    'name' => $name,
                    'eventIds' => [
                        $eventId1,
                        $eventId2,
                    ],
                ]
            )
            ->build('POST');

        $this->commandBus->record();

        $response = $this->createProductionRequestHandler->handle($request);

        $this->assertCount(1, $this->commandBus->getRecordedCommands());
        $recordedCommand = $this->commandBus->getRecordedCommands()[0];
        $this->assertInstanceOf(GroupEventsAsProduction::class, $recordedCommand);
        $this->assertEquals($name, $recordedCommand->getName());
        $this->assertEquals([$eventId1, $eventId2], $recordedCommand->getEventIds());

        $this->assertJsonResponse(
            new JsonLdResponse(['productionId' => $recordedCommand->getItemId()], 201),
            $response
        );
    }
}
