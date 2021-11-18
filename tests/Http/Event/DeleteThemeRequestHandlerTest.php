<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Commands\RemoveTheme;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\TestCase;

class DeleteThemeRequestHandlerTest extends TestCase
{
    private TraceableCommandBus $commandBus;

    private DeleteThemeRequestHandler $deleteThemeRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->deleteThemeRequestHandler = new DeleteThemeRequestHandler($this->commandBus);

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_handles_deleting_a_theme(): void
    {
        $deleteThemeRequest = $this->psr7RequestBuilder
            ->withRouteParameter('eventId', '609a8214-51c9-48c0-903f-840a4f38852f')
            ->build('DELETE');

        $this->deleteThemeRequestHandler->handle($deleteThemeRequest);

        $this->assertEquals(
            [
                new RemoveTheme('609a8214-51c9-48c0-903f-840a4f38852f'),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }
}
