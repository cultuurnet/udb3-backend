<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateBirthdateRange;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Audience\BirthdateRange;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class UpdateBirthdateRangeRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private const EVENT_ID = '609a8214-51c9-48c0-903f-840a4f38852f';

    private TraceableCommandBus $commandBus;

    private UpdateBirthdateRangeRequestHandler $handler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->handler = new UpdateBirthdateRangeRequestHandler($this->commandBus);
        $this->psr7RequestBuilder = new Psr7RequestBuilder();
        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_dispatches_update_birthdate_range(): void
    {
        $request = $this->psr7RequestBuilder
            ->withRouteParameter('eventId', self::EVENT_ID)
            ->withJsonBodyFromArray([
                'birthdateRange' => [
                    'from' => '2014-01-01',
                    'to' => '2020-12-31',
                ],
            ])
            ->build('PUT');

        $response = $this->handler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals(
            [new UpdateBirthdateRange(
                self::EVENT_ID,
                new BirthdateRange(
                    new DateTimeImmutable('2014-01-01'),
                    new DateTimeImmutable('2020-12-31')
                )
            )],
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
            'missing birthdateRange' => [
                '{}',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/', 'The required properties (birthdateRange) are missing')
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
            ->withJsonBodyFromArray([
                'birthdateRange' => [
                    'from' => '2020-12-31',
                    'to' => '2014-01-01',
                ],
            ])
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/birthdateRange', '"From" birthdate should not be greater than the "to" birthdate.')
            ),
            fn () => $this->handler->handle($request)
        );
    }
}
