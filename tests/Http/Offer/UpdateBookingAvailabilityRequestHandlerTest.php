<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Offer\Commands\UpdateBookingAvailability;
use CultuurNet\UDB3\Offer\ValueObjects\BookingAvailability;
use PHPUnit\Framework\TestCase;

class UpdateBookingAvailabilityRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;
    private UpdateBookingAvailabilityRequestHandler $requestHandler;

    private Psr7RequestBuilder $requestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->requestHandler = new UpdateBookingAvailabilityRequestHandler(
            $this->commandBus,
            new UpdateBookingAvailabilityRequestBodyParser()
        );
        $this->requestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_allows_valid_data(): void
    {
        $given = $this->requestBuilder->withBodyFromString('{"type":"Available"}')->build('PUT');

        $this->requestHandler->handle($given, '609a8214-51c9-48c0-903f-840a4f38852f');

        $this->assertEquals(
            [
                new UpdateBookingAvailability(
                    '609a8214-51c9-48c0-903f-840a4f38852f',
                    BookingAvailability::available()
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_fails_on_empty_body(): void
    {
        $given = $this->requestBuilder->withBodyFromString('')->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyMissing(),
            fn () => $this->requestHandler->handle($given, '609a8214-51c9-48c0-903f-840a4f38852f')
        );

        $this->assertEquals([], $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_fails_on_unparsable_body(): void
    {
        $given = $this->requestBuilder->withBodyFromString('{{}')->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidSyntax('JSON'),
            fn () => $this->requestHandler->handle($given, '609a8214-51c9-48c0-903f-840a4f38852f')
        );

        $this->assertEquals([], $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_fails_on_missing_type(): void
    {
        $given = $this->requestBuilder->withBodyFromString('{}')->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(new SchemaError('/', 'The required properties (type) are missing')),
            fn () => $this->requestHandler->handle($given, '609a8214-51c9-48c0-903f-840a4f38852f')
        );

        $this->assertEquals([], $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_fails_on_invalid_type(): void
    {
        $given = $this->requestBuilder->withBodyFromString('{"type":"foo"}')->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(new SchemaError('/type', 'The data should match one item from enum')),
            fn () => $this->requestHandler->handle($given, '609a8214-51c9-48c0-903f-840a4f38852f')
        );

        $this->assertEquals([], $this->commandBus->getRecordedCommands());
    }
}
