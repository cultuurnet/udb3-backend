<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Commands\DeleteBirthdateRange;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\TestCase;

final class DeleteBirthdateRangeRequestHandlerTest extends TestCase
{
    private const EVENT_ID = '609a8214-51c9-48c0-903f-840a4f38852f';

    private TraceableCommandBus $commandBus;

    private DeleteBirthdateRangeRequestHandler $handler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->handler = new DeleteBirthdateRangeRequestHandler($this->commandBus);
        $this->psr7RequestBuilder = new Psr7RequestBuilder();
        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_dispatches_delete_birthdate_range(): void
    {
        $request = $this->psr7RequestBuilder
            ->withRouteParameter('eventId', self::EVENT_ID)
            ->build('DELETE');

        $response = $this->handler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals(
            [new DeleteBirthdateRange(self::EVENT_ID)],
            $this->commandBus->getRecordedCommands()
        );
    }
}
