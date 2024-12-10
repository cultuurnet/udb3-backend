<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Organizer\Commands\RemoveImage;
use PHPUnit\Framework\TestCase;

final class DeleteImageRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;

    private DeleteImageRequestHandler $deleteImageRequestHandler;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->deleteImageRequestHandler = new DeleteImageRequestHandler($this->commandBus);

        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_handles_removing_an_image(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', 'c269632a-a887-4f21-8455-1631c31e4df5')
            ->withRouteParameter('imageId', '03789a2f-5063-4062-b7cb-95a0a2280d92')
            ->build('DELETE');

        $response = $this->deleteImageRequestHandler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals(
            [
                new RemoveImage(
                    'c269632a-a887-4f21-8455-1631c31e4df5',
                    new UUID('03789a2f-5063-4062-b7cb-95a0a2280d92')
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }
}
