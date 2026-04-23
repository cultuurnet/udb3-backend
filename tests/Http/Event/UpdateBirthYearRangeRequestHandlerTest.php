<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateBirthYearRange;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Audience\BirthYearRange;
use PHPUnit\Framework\TestCase;

final class UpdateBirthYearRangeRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private const EVENT_ID = '609a8214-51c9-48c0-903f-840a4f38852f';

    private TraceableCommandBus $commandBus;

    private UpdateBirthYearRangeRequestHandler $handler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->handler = new UpdateBirthYearRangeRequestHandler($this->commandBus);
        $this->psr7RequestBuilder = new Psr7RequestBuilder();
        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_dispatches_update_birth_year_range(): void
    {
        $request = $this->psr7RequestBuilder
            ->withRouteParameter('eventId', self::EVENT_ID)
            ->withJsonBodyFromArray(['birthYear' => '2014-2020'])
            ->build('PUT');

        $response = $this->handler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals(
            [new UpdateBirthYearRange(self::EVENT_ID, new BirthYearRange(2014, 2020))],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_dispatches_update_with_single_year(): void
    {
        $request = $this->psr7RequestBuilder
            ->withRouteParameter('eventId', self::EVENT_ID)
            ->withJsonBodyFromArray(['birthYear' => '2014'])
            ->build('PUT');

        $response = $this->handler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals(
            [new UpdateBirthYearRange(self::EVENT_ID, new BirthYearRange(2014, 2014))],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     * @dataProvider invalidBody
     */
    public function it_throws_an_api_problem_for_an_invalid_body(string $body, ApiProblem $expectedApiProblem): void
    {
        $request = $this->psr7RequestBuilder
            ->withRouteParameter('eventId', self::EVENT_ID)
            ->withBodyFromString($body)
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            $expectedApiProblem,
            fn () => $this->handler->handle($request)
        );
    }

    public function invalidBody(): array
    {
        return [
            'missing body' => [
                '',
                ApiProblem::bodyMissing(),
            ],
            'invalid syntax' => [
                '{{}',
                ApiProblem::bodyInvalidSyntax('JSON'),
            ],
            'missing birthYear' => [
                '{}',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/', 'The required properties (birthYear) are missing')
                ),
            ],
            'invalid format' => [
                '{"birthYear": "abc"}',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/birthYear', 'The string should match pattern: ^[12]\\d{3}(-[12]\\d{3})?$')
                ),
            ],
        ];
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_for_invalid_range(): void
    {
        $request = $this->psr7RequestBuilder
            ->withRouteParameter('eventId', self::EVENT_ID)
            ->withJsonBodyFromArray(['birthYear' => '2020-2014'])
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/birthYear', '"From" birth year should not be greater than the "to" birth year.')
            ),
            fn () => $this->handler->handle($request)
        );
    }
}
