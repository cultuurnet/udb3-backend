<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\TestCase;

final class RemainingCapacityValidatingRequestBodyParserTest extends TestCase
{
    use AssertApiProblemTrait;

    private RemainingCapacityValidatingRequestBodyParser $parser;

    private Psr7RequestBuilder $requestBuilder;

    protected function setUp(): void
    {
        $this->parser = new RemainingCapacityValidatingRequestBodyParser();
        $this->requestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     */
    public function it_returns_the_request_unchanged_when_body_is_not_an_object(): void
    {
        $request = $this->requestBuilder
            ->build('POST')
            ->withParsedBody(['bookingAvailability' => ['remainingCapacity' => 10]]);

        $this->assertSame($request, $this->parser->parse($request));
    }

    /**
     * @test
     */
    public function it_returns_the_request_unchanged_when_bookingAvailability_is_missing(): void
    {
        $body = (object) [
            'mainLanguage' => 'nl',
            'name' => (object) ['nl' => 'Some event'],
        ];

        $request = $this->requestBuilder
            ->build('POST')
            ->withParsedBody($body);

        $this->assertSame($request, $this->parser->parse($request));
    }

    /**
     * @test
     */
    public function it_returns_the_request_unchanged_when_bookingAvailability_is_a_scalar(): void
    {
        $body = (object) [
            'bookingAvailability' => 'Available',
        ];

        $request = $this->requestBuilder
            ->build('POST')
            ->withParsedBody($body);

        $this->assertSame($request, $this->parser->parse($request));
    }

    /**
     * @test
     */
    public function it_returns_the_request_unchanged_when_only_subEvent_carries_remainingCapacity(): void
    {
        $body = (object) [
            'calendarType' => 'single',
            'bookingAvailability' => (object) [
                'type' => 'Available',
            ],
            'subEvent' => [
                (object) [
                    'startDate' => '2026-01-01T00:00:00+00:00',
                    'endDate' => '2026-01-01T23:59:59+00:00',
                    'bookingAvailability' => (object) [
                        'type' => 'Available',
                        'remainingCapacity' => 5,
                    ],
                ],
            ],
        ];

        $request = $this->requestBuilder
            ->build('POST')
            ->withParsedBody($body);

        $this->assertSame($request, $this->parser->parse($request));
    }

    /**
     * @test
     */
    public function it_throws_when_top_level_bookingAvailability_has_remainingCapacity(): void
    {
        $body = (object) [
            'bookingAvailability' => (object) [
                'type' => 'Available',
                'remainingCapacity' => 10,
            ],
        ];

        $request = $this->requestBuilder
            ->build('POST')
            ->withParsedBody($body);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/bookingAvailability/remainingCapacity',
                    'remainingCapacity can only be set on a sub-event entry, not on the top-level bookingAvailability. Set it under /subEvent/{index}/bookingAvailability instead.'
                )
            ),
            fn () => $this->parser->parse($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_when_top_level_remainingCapacity_is_null(): void
    {
        $body = (object) [
            'bookingAvailability' => (object) [
                'type' => 'Available',
                'remainingCapacity' => null,
            ],
        ];

        $request = $this->requestBuilder
            ->build('POST')
            ->withParsedBody($body);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/bookingAvailability/remainingCapacity',
                    'remainingCapacity can only be set on a sub-event entry, not on the top-level bookingAvailability. Set it under /subEvent/{index}/bookingAvailability instead.'
                )
            ),
            fn () => $this->parser->parse($request)
        );
    }
}
