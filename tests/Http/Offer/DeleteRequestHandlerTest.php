<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Offer\Commands\AbstractDeleteOffer;
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
    public function it_dispatches_a_delete_command_for_events(): void
    {
        $eventId = '9a79a8f4-3602-4852-91b9-9132167837f7';

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', $eventId)
            ->build('DELETE');

        $expectedCommand = new AbstractDeleteOffer($eventId);

        $this->deleteRequestHandler->handle($request);

        $this->assertEquals([$expectedCommand], $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_dispatches_a_delete_command_for_places(): void
    {
        $placeId = '9a79a8f4-3602-4852-91b9-9132167837f7';

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'places')
            ->withRouteParameter('offerId', $placeId)
            ->build('DELETE');

        $expectedCommand = new AbstractDeleteOffer($placeId);

        $this->deleteRequestHandler->handle($request);

        $this->assertEquals([$expectedCommand], $this->commandBus->getRecordedCommands());
    }
}
