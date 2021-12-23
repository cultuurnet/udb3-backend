<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Organizer\Commands\DeleteOrganizer;
use PHPUnit\Framework\TestCase;

class DeleteOrganizerRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;

    private DeleteOrganizerRequestHandler $deleteOrganizerRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->deleteOrganizerRequestHandler = new DeleteOrganizerRequestHandler($this->commandBus);

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_handles_deleting_the_address(): void
    {
        $deleteAddressRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'a088f396-ac96-45c4-b6b2-e2b6afe8af07')
            ->build('DELETE');

        $this->deleteOrganizerRequestHandler->handle($deleteAddressRequest);

        $this->assertEquals(
            [
                new DeleteOrganizer('a088f396-ac96-45c4-b6b2-e2b6afe8af07'),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }
}
