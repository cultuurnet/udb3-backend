<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\TestCase;

final class BookingAvailabilityCapacityValidatingRequestBodyParserTest extends TestCase
{
    use AssertApiProblemTrait;

    private BookingAvailabilityCapacityValidatingRequestBodyParser $parser;
    private Psr7RequestBuilder $requestBuilder;

    protected function setUp(): void
    {
        $this->parser = new BookingAvailabilityCapacityValidatingRequestBodyParser();
        $this->requestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     */
    public function it_passes_through_when_remainingCapacity_is_less_than_capacity(): void
    {
        $body = (object) ['capacity' => 100, 'remainingCapacity' => 42];

        $request = $this->requestBuilder->build('PUT')->withParsedBody($body);

        $result = $this->parser->parse($request)->getParsedBody();

        $this->assertEquals($body, $result);
    }

    /**
     * @test
     */
    public function it_passes_through_when_remainingCapacity_equals_capacity(): void
    {
        $body = (object) ['capacity' => 100, 'remainingCapacity' => 100];

        $request = $this->requestBuilder->build('PUT')->withParsedBody($body);

        $result = $this->parser->parse($request)->getParsedBody();

        $this->assertEquals($body, $result);
    }

    /**
     * @test
     */
    public function it_passes_through_when_only_capacity_is_set(): void
    {
        $body = (object) ['type' => 'Available', 'capacity' => 100];

        $request = $this->requestBuilder->build('PUT')->withParsedBody($body);

        $result = $this->parser->parse($request)->getParsedBody();

        $this->assertEquals($body, $result);
    }

    /**
     * @test
     */
    public function it_passes_through_when_only_remainingCapacity_is_set(): void
    {
        $body = (object) ['remainingCapacity' => 42];

        $request = $this->requestBuilder->build('PUT')->withParsedBody($body);

        $result = $this->parser->parse($request)->getParsedBody();

        $this->assertEquals($body, $result);
    }

    /**
     * @test
     */
    public function it_throws_when_remainingCapacity_exceeds_capacity(): void
    {
        $body = (object) ['capacity' => 50, 'remainingCapacity' => 100];

        $request = $this->requestBuilder->build('PUT')->withParsedBody($body);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/remainingCapacity', 'remainingCapacity must be less than or equal to capacity')
            ),
            fn () => $this->parser->parse($request)
        );
    }

    /**
     * @test
     */
    public function it_passes_through_when_remainingCapacity_is_zero(): void
    {
        $body = (object) ['capacity' => 100, 'remainingCapacity' => 0];

        $request = $this->requestBuilder->build('PUT')->withParsedBody($body);

        $result = $this->parser->parse($request)->getParsedBody();

        $this->assertEquals($body, $result);
    }
}
