<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Commands\UpdateTitle;
use PHPUnit\Framework\TestCase;

final class UpdateTitleRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;
    private UpdateTitleRequestHandler $updateTitleRequestHandler;

    private Psr7RequestBuilder $requestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->updateTitleRequestHandler = new UpdateTitleRequestHandler($this->commandBus);
        $this->requestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     * @dataProvider offerTypeParameterProvider
     */
    public function it_handles_a_name_change(string $offerType): void
    {
        $given = $this->requestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', '609a8214-51c9-48c0-903f-840a4f38852f')
            ->withRouteParameter('language', 'en')
            ->withBodyFromString('{"name":"New name"}')
            ->build('PUT');

        $this->updateTitleRequestHandler->handle($given);

        $this->assertEquals(
            [
                new UpdateTitle(
                    '609a8214-51c9-48c0-903f-840a4f38852f',
                    new Language('en'),
                    new Title('New name')
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
            fn () => $this->updateTitleRequestHandler->handle($given)
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
            fn () => $this->updateTitleRequestHandler->handle($given)
        );

        $this->assertEquals([], $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     * @dataProvider offerTypeParameterProvider
     */
    public function it_fails_on_missing_name(string $offerType): void
    {
        $given = $this->requestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', '609a8214-51c9-48c0-903f-840a4f38852f')
            ->withBodyFromString('{}')
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(new SchemaError('/', 'The required properties (name) are missing')),
            fn () => $this->updateTitleRequestHandler->handle($given)
        );

        $this->assertEquals([], $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     * @dataProvider offerTypeParameterProvider
     */
    public function it_fails_on_invalid_name_format(string $offerType): void
    {
        $given = $this->requestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', '609a8214-51c9-48c0-903f-840a4f38852f')
            ->withBodyFromString('{"name":123}')
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(new SchemaError('/name', 'The data (integer) must match the type: string')),
            fn () => $this->updateTitleRequestHandler->handle($given)
        );

        $this->assertEquals([], $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     * @dataProvider offerTypeParameterProvider
     */
    public function it_fails_on_empty_name(string $offerType): void
    {
        $given = $this->requestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', '609a8214-51c9-48c0-903f-840a4f38852f')
            ->withBodyFromString('{"name":"   "}')
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(new SchemaError('/name', 'The string should match pattern: \S')),
            fn () => $this->updateTitleRequestHandler->handle($given)
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
