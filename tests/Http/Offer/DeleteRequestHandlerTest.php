<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Offer\Commands\DeleteOffer;
use PHPUnit\Framework\TestCase;

class DeleteRequestHandlerTest extends TestCase
{
    private TraceableCommandBus $commandBus;
    private DeleteRequestHandler $deleteRequestHandler;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->deleteRequestHandler = new DeleteRequestHandler($this->commandBus);
        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_dispatches_a_delete_command(): void
    {
        $eventId = '9a79a8f4-3602-4852-91b9-9132167837f7';

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerId', $eventId)
            ->build('DELETE');

        $expectedCommand = new DeleteOffer($eventId);

        $this->deleteRequestHandler->handle($request);

        $this->assertEquals([$expectedCommand], $this->commandBus->getRecordedCommands());
    }
}
