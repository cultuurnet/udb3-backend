<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Organizer\Commands\DeleteEducationalDescription;
use PHPUnit\Framework\TestCase;

final class DeleteEducationalDescriptionRequestHandlerTest extends TestCase
{
    private TraceableCommandBus $commandBus;

    private DeleteEducationalDescriptionRequestHandler $handler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->handler = new DeleteEducationalDescriptionRequestHandler($this->commandBus);
        $this->psr7RequestBuilder = new Psr7RequestBuilder();
        $this->commandBus->record();
    }

    /**
     * @test
     * @group educationalDescription
     */
    public function it_handles_deleting_an_educational_description(): void
    {
        $request = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'c269632a-a887-4f21-8455-1631c31e4df5')
            ->withRouteParameter('language', 'nl')
            ->build('DELETE');

        $expectedCommand = new DeleteEducationalDescription(
            'c269632a-a887-4f21-8455-1631c31e4df5',
            new Language('nl')
        );

        $response = $this->handler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals([$expectedCommand], $this->commandBus->getRecordedCommands());
    }
}
