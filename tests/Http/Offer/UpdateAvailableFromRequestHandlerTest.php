<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Offer\Commands\UpdateAvailableFrom;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class UpdateAvailableFromRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;
    private UpdateAvailableFromRequestHandler $updateAvailableFromRequestHandler;

    private Psr7RequestBuilder $requestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->updateAvailableFromRequestHandler = new UpdateAvailableFromRequestHandler($this->commandBus);
        $this->requestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     * @dataProvider offerTypeParameterProvider
     */
    public function it_handles_update_available_from(string $offerType): void
    {
        $given = $this->requestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', '609a8214-51c9-48c0-903f-840a4f38852f')
            ->withBodyFromString('{"availableFrom":"2030-10-10T11:22:33+00:00"}')
            ->build('PUT');

        $this->updateAvailableFromRequestHandler->handle($given);

        $this->assertEquals(
            [
                new UpdateAvailableFrom(
                    '609a8214-51c9-48c0-903f-840a4f38852f',
                    new DateTimeImmutable('2030-10-10T11:22:33+00:00')
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     * @dataProvider offerTypeParameterProvider
     */
    public function it_fails_on_empty_body(string $offerType): void
    {
        $given = $this->requestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', '609a8214-51c9-48c0-903f-840a4f38852f')
            ->withBodyFromString('')
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyMissing(),
            fn () => $this->updateAvailableFromRequestHandler->handle($given)
        );

        $this->assertEquals([], $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     * @dataProvider offerTypeParameterProvider
     */
    public function it_fails_on_unparsable_body(string $offerType): void
    {
        $given = $this->requestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', '609a8214-51c9-48c0-903f-840a4f38852f')
            ->withBodyFromString('{{}')
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidSyntax('JSON'),
            fn () => $this->updateAvailableFromRequestHandler->handle($given)
        );

        $this->assertEquals([], $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     * @dataProvider offerTypeParameterProvider
     */
    public function it_fails_on_missing_available_from(string $offerType): void
    {
        $given = $this->requestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', '609a8214-51c9-48c0-903f-840a4f38852f')
            ->withBodyFromString('{}')
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(new SchemaError('/', 'The required properties (availableFrom) are missing')),
            fn () => $this->updateAvailableFromRequestHandler->handle($given)
        );

        $this->assertEquals([], $this->commandBus->getRecordedCommands());
    }

    public function offerTypeParameterProvider(): array
    {
        return [
            ['events'],
            ['places'],
        ];
    }
}
