<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Commands\DeleteTypicalBirthDate;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use PHPUnit\Framework\TestCase;

final class DeleteTypicalBirthDateRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private const EVENT_ID = 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b';

    private TraceableCommandBus $commandBus;

    private DeleteTypicalBirthDateRequestHandler $handler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->handler = new DeleteTypicalBirthDateRequestHandler($this->commandBus);
        $this->psr7RequestBuilder = new Psr7RequestBuilder();
        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_handles_deleting_the_typical_birth_date(): void
    {
        $request = $this->psr7RequestBuilder
            ->withRouteParameter('eventId', self::EVENT_ID)
            ->build('DELETE');

        $response = $this->handler->handle($request);

        $this->assertEquals(
            [new DeleteTypicalBirthDate(self::EVENT_ID)],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertJsonResponse(new NoContentResponse(), $response);
    }
}
