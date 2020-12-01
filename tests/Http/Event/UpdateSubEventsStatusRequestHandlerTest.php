<?php

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\Deserializer\DataValidationException;
use CultuurNet\UDB3\Event\Commands\UpdateSubEventsStatus;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Event\ValueObjects\StatusReason;
use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\Http\HttpFoundation\NoContent;
use CultuurNet\UDB3\Language;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class UpdateSubEventsStatusRequestHandlerTest extends TestCase
{
    /**
     * @var UpdateSubEventsStatusRequestHandler
     */
    private $requestHandler;

    /**
     * @var TraceableCommandBus
     */
    private $commandBus;

    protected function setUp()
    {
        parent::setUp();

        $this->commandBus = new TraceableCommandBus();

        $this->requestHandler = new UpdateSubEventsStatusRequestHandler(
            $this->commandBus,
            new UpdateSubEventsStatusValidator()
        );
    }

    /**
     * @test
     */
    public function it_should_dispatch_a_UpdateSubEventsStatus_command(): void
    {
        $eventId = '4f480c25-f72f-42b4-8df3-37e8a14f6133';
        $payload = [
            [
                'id' => 2,
                'status' => [
                    'type' => 'Unavailable',
                ],
            ],
            [
                'id' => 4,
                'status' => [
                    'type' => 'TemporarilyUnavailable',
                    'reason' => [
                        'nl' => 'Nederlandse reden',
                        'fr' => 'Franse reden',
                    ],
                ],
            ],
        ];

        $request = new Request([], [], [], [], [], [], json_encode($payload));

        $expected = (new UpdateSubEventsStatus($eventId))
            ->withUpdatedStatus(2, new Status(StatusType::unavailable(), []))
            ->withUpdatedStatus(
                4,
                new Status(
                    StatusType::temporarilyUnavailable(),
                    [
                        new StatusReason(new Language('nl'), 'Nederlandse reden'),
                        new StatusReason(new Language('fr'), 'Franse reden'),
                    ]
                )
            );

        $this->commandBus->record();

        $response = $this->requestHandler->handle($request, $eventId);

        $actual = $this->commandBus->getRecordedCommands();

        $this->assertEquals((new NoContent())->getStatusCode(), $response->getStatusCode());
        $this->assertEquals([$expected], $actual);
    }

    /**
     * @test
     */
    public function it_should_validate_the_payload_first(): void
    {
        $eventId = '4f480c25-f72f-42b4-8df3-37e8a14f6133';
        $payload = [
            [
                'id' => 2,
                'status' => [],
            ],
        ];

        $request = new Request([], [], [], [], [], [], json_encode($payload));

        $this->expectException(DataValidationException::class);

        $this->requestHandler->handle($request, $eventId);
    }
}
