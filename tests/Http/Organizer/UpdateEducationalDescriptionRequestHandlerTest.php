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
use CultuurNet\UDB3\Organizer\Commands\UpdateEducationalDescription;
use PHPUnit\Framework\TestCase;

final class UpdateEducationalDescriptionRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;

    private UpdateEducationalDescriptionRequestHandler $handler;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->handler = new UpdateEducationalDescriptionRequestHandler($this->commandBus);

        $this->commandBus->record();
    }

    /**
     * @test
     * @group educationalDescription
     */
    public function it_handles_updating_description(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', 'c269632a-a887-4f21-8455-1631c31e4df5')
            ->withRouteParameter('language', 'nl')
            ->withJsonBodyFromArray(['educationalDescription' => 'Een zeer opvoedkundige beschrijving'])
            ->build('PUT');

        $expectedCommand = new UpdateEducationalDescription(
            'c269632a-a887-4f21-8455-1631c31e4df5',
            new Description('Een zeer opvoedkundige beschrijving'),
            new Language('nl')
        );

        $response = $this->handler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals([$expectedCommand], $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     * @dataProvider invalidBodyDataProvider
     * @group educationalDescription
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
            fn () => $this->handler->handle($request)
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
                    new SchemaError('/', 'The required properties (educationalDescription) are missing')
                ),
            ],
            [
                '{"educationalDescription": 1}',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/educationalDescription', 'The data (integer) must match the type: string')
                ),
            ],
            [
                '{"educationalDescription": ""}',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/educationalDescription', 'Minimum string length is 1, found 0')
                ),
            ],
            [
                '{"educationalDescription": "     "}',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/educationalDescription', 'The string should match pattern: \S')
                ),
            ],
        ];
    }
}
