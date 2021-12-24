<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Organizer\Commands\UpdateOrganizer;
use PHPUnit\Framework\TestCase;

final class UpdateOrganizerRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;

    private UpdateOrganizerRequestHandler $updateOrganizerRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->updateOrganizerRequestHandler = new UpdateOrganizerRequestHandler($this->commandBus);

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_handles_updating_an_organizer(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', 'c269632a-a887-4f21-8455-1631c31e4df5')
            ->withBodyFromArray([
                'mainImageId' => '03789a2f-5063-4062-b7cb-95a0a2280d92',
            ])
            ->build('PATCH');

        $expectedCommand = (new UpdateOrganizer('c269632a-a887-4f21-8455-1631c31e4df5'))
            ->withMainImageId(new UUID('03789a2f-5063-4062-b7cb-95a0a2280d92'));

        $response = $this->updateOrganizerRequestHandler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals([$expectedCommand], $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     * @dataProvider invalidBodyDataProvider
     */
    public function it_throws_an_api_problem_for_an_invalid_body(string $body, ApiProblem $expectedApiProblem): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', 'c269632a-a887-4f21-8455-1631c31e4df5')
            ->withBodyFromString($body)
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            $expectedApiProblem,
            fn () => $this->updateOrganizerRequestHandler->handle($request)
        );
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            [
                '',
                ApiProblem::bodyMissing(),
            ],
            [
                '{{}',
                ApiProblem::bodyInvalidSyntax('JSON'),
            ],
            [
                '{}',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/', 'Object must have at least 1 properties, 0 found')
                ),
            ],
            [
                '{
                    "mainImageId":"not a uuid"
                }',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/mainImageId', 'The data must match the \'uuid\' format')
                ),
            ],
        ];
    }
}
