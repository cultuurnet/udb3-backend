<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Productions;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Productions\RejectSuggestedEventPair;
use CultuurNet\UDB3\Event\Productions\SimilarEventPair;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

final class SkipEventsRequestHandlerTest extends TestCase
{
    private TraceableCommandBus $commandBus;

    private SkipEventsRequestHandler $skipEventsRequestHandler;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->skipEventsRequestHandler = new SkipEventsRequestHandler(
            $this->commandBus,
            new SkipEventsValidator()
        );
    }

    /**
     * @test
     */
    public function it_can_skip_events(): void
    {
        $eventId1 = UUID::uuid4()->toString();
        $eventId2 = UUID::uuid4()->toString();

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray(
                [
                    'eventIds' => [
                        $eventId1,
                        $eventId2,
                    ],
                ]
            )
            ->build('POST');

        $this->commandBus->record();

        $response = $this->skipEventsRequestHandler->handle($request);

        $this->assertEquals(
            [new RejectSuggestedEventPair(new SimilarEventPair($eventId1, $eventId2))],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertEquals(200, $response->getStatusCode());
    }
}
