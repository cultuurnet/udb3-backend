<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Productions;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Event\Productions\GroupEventsAsProduction;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

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

    /**
     * @test
     */
    public function it_can_create_production_with_single_event(): void
    {
        $eventId1 = Uuid::uuid4()->toString();
        $name = 'Singing in the drain';

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray(
                [
                    'name' => $name,
                    'eventIds' => [
                        $eventId1,
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
        $this->assertEquals([$eventId1], $recordedCommand->getEventIds());

        $this->assertJsonResponse(
            new JsonLdResponse(['productionId' => $recordedCommand->getItemId()], 201),
            $response
        );
    }

    /**
     * @test
     * @dataProvider invalidDataProvider
     */
    public function it_validates_incoming_data_to_create_a_production(array $invalidData, array $messages): void
    {
        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($invalidData)
            ->build('POST');

        $dataValidationException = new DataValidationException();
        $dataValidationException->setValidationMessages($messages);

        $this->assertDataValidationException(
            $dataValidationException,
            fn () => $this->createProductionRequestHandler->handle($request),
        );
    }

    public function invalidDataProvider(): array
    {
        return [
            'missing_name' => [
                [
                    'eventIds' => ['275a6c2b-4374-4ba5-a90b-3e268de24f5a'],
                ],
                [
                    'name' => 'Required but could not be found',
                ],
            ],
            'empty_name' => [
                [
                    'name' => '',
                    'eventIds' => ['275a6c2b-4374-4ba5-a90b-3e268de24f5a'],
                ],
                [
                    'name' => 'Cannot be empty',
                ],
            ],
            'missing_event_ids' => [
                [
                    'name' => 'name',
                ],
                [
                    'eventIds' => 'Required but could not be found',
                ],
            ],
            'empty_event_ids' => [
                [
                    'name' => 'name',
                    'eventIds' => [],
                ],
                [
                    'eventIds' => 'At least one event should be provided',
                ],
            ],
        ];
    }

    private function assertDataValidationException(
        DataValidationException $dataValidationException,
        callable $callback
    ): void {
        try {
            $callback();
        } catch (DataValidationException $exception) {
            $this->assertEquals($dataValidationException->getMessage(), $exception->getMessage());
            $this->assertEquals($dataValidationException->getValidationMessages(), $exception->getValidationMessages());
            return;
        }
    }
}
