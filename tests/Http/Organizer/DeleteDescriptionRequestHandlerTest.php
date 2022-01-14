<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Organizer\Commands\DeleteDescription;
use PHPUnit\Framework\TestCase;

final class DeleteDescriptionRequestHandlerTest extends TestCase
{
    private TraceableCommandBus $commandBus;

    private DeleteDescriptionRequestHandler $deleteDescriptionRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->deleteDescriptionRequestHandler = new DeleteDescriptionRequestHandler($this->commandBus);

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_handles_deleting_a_description(): void
    {
        $request = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'c269632a-a887-4f21-8455-1631c31e4df5')
            ->withRouteParameter('language', 'nl')
            ->build('DELETE');

        $expectedCommand = new DeleteDescription(
            'c269632a-a887-4f21-8455-1631c31e4df5',
            new Language('nl')
        );

        $response = $this->deleteDescriptionRequestHandler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals([$expectedCommand], $this->commandBus->getRecordedCommands());
    }
}
