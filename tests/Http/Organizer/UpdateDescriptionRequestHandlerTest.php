<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Organizer\Commands\UpdateDescription;
use PHPUnit\Framework\TestCase;

final class UpdateDescriptionRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;

    private UpdateDescriptionRequestHandler $updateDescriptionRequestHandler;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->updateDescriptionRequestHandler = new UpdateDescriptionRequestHandler($this->commandBus);

        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_handles_updating_description(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', 'c269632a-a887-4f21-8455-1631c31e4df5')
            ->withRouteParameter('language', 'nl')
            ->withJsonBodyFromArray(['description' => 'Beschrijving'])
            ->build('PUT');

        $expectedCommand = new UpdateDescription(
            'c269632a-a887-4f21-8455-1631c31e4df5',
            new Description('Beschrijving'),
            new Language('nl')
        );

        $response = $this->updateDescriptionRequestHandler->handle($request);

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
            ->withRouteParameter('language', 'nl')
            ->withBodyFromString($body)
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            $expectedApiProblem,
            fn () => $this->updateDescriptionRequestHandler->handle($request)
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
                    new SchemaError('/', 'The required properties (description) are missing')
                ),
            ],
            [
                '{"description": 1}',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/description', 'The data (integer) must match the type: string')
                ),
            ],
            [
                '{"description": ""}',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/description', 'Minimum string length is 1, found 0')
                ),
            ],
            [
                '{"description": "     "}',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/description', 'The string should match pattern: \S')
                ),
            ],
        ];
    }
}
