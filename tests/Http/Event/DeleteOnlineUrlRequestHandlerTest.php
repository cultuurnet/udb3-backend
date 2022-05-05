<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Commands\DeleteOnlineUrl;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\TestCase;

final class DeleteOnlineUrlRequestHandlerTest extends TestCase
{
    private TraceableCommandBus $commandBus;

    private DeleteOnlineUrlRequestHandler $deleteOnlineUrlRequestHandler;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->commandBus->record();

        $this->deleteOnlineUrlRequestHandler = new DeleteOnlineUrlRequestHandler($this->commandBus);
    }

    /**
     * @test
     */
    public function it_dispatches_delete_onlineUrl(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', 'c269632a-a887-4f21-8455-1631c31e4df5')
            ->build('DELETE');

        $response = $this->deleteOnlineUrlRequestHandler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals(
            [
                new DeleteOnlineUrl('c269632a-a887-4f21-8455-1631c31e4df5'),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }
}
