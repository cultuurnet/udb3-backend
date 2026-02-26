<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\TestCase;

final class SubEventCapacityValidatingRequestBodyParserTest extends TestCase
{
    use AssertApiProblemTrait;

    private SubEventCapacityValidatingRequestBodyParser $parser;
    private Psr7RequestBuilder $requestBuilder;

    protected function setUp(): void
    {
        $this->parser = new SubEventCapacityValidatingRequestBodyParser();
        $this->requestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     */
    public function it_passes_through_a_valid_body_with_remainingCapacity_and_capacity(): void
    {
        $body = [
            (object) [
                'id' => 0,
                'bookingAvailability' => (object) [
                    'capacity' => 100,
                    'remainingCapacity' => 42,
                ],
            ],
        ];

        $request = $this->requestBuilder->build('PATCH')->withParsedBody($body);

        $result = $this->parser->parse($request)->getParsedBody();

        $this->assertEquals($body, $result);
    }

    /**
     * @test
     */
    public function it_passes_through_a_valid_body_with_only_type(): void
    {
        $body = [
            (object) [
                'id' => 0,
                'bookingAvailability' => (object) [
                    'type' => 'Available',
                ],
            ],
        ];

        $request = $this->requestBuilder->build('PATCH')->withParsedBody($body);

        $result = $this->parser->parse($request)->getParsedBody();

        $this->assertEquals($body, $result);
    }

    /**
     * @test
     */
    public function it_passes_through_a_valid_body_with_status_but_no_remainingCapacity(): void
    {
        $body = [
            (object) [
                'id' => 0,
                'status' => (object) ['type' => 'Available'],
                'bookingAvailability' => (object) ['type' => 'Available'],
            ],
        ];

        $request = $this->requestBuilder->build('PATCH')->withParsedBody($body);

        $result = $this->parser->parse($request)->getParsedBody();

        $this->assertEquals($body, $result);
    }

    /**
     * @test
     */
    public function it_throws_when_remainingCapacity_exceeds_capacity(): void
    {
        $body = [
            (object) [
                'id' => 0,
                'bookingAvailability' => (object) [
                    'capacity' => 50,
                    'remainingCapacity' => 100,
                ],
            ],
        ];

        $request = $this->requestBuilder->build('PATCH')->withParsedBody($body);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/0/bookingAvailability/remainingCapacity', 'remainingCapacity must be less than or equal to capacity')
            ),
            fn () => $this->parser->parse($request)
        );
    }

    /**
     * @test
     */
    public function it_reports_errors_for_multiple_sub_events(): void
    {
        $body = [
            (object) [
                'id' => 0,
                'bookingAvailability' => (object) [
                    'capacity' => 10,
                    'remainingCapacity' => 99,
                ],
            ],
            (object) [
                'id' => 1,
                'bookingAvailability' => (object) [
                    'capacity' => 50,
                    'remainingCapacity' => 100,
                ],
            ],
        ];

        $request = $this->requestBuilder->build('PATCH')->withParsedBody($body);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/0/bookingAvailability/remainingCapacity', 'remainingCapacity must be less than or equal to capacity'),
                new SchemaError('/1/bookingAvailability/remainingCapacity', 'remainingCapacity must be less than or equal to capacity')
            ),
            fn () => $this->parser->parse($request)
        );
    }

    /**
     * @test
     */
    public function it_reports_errors_for_multiple_sub_events(): void
    {
        $body = [
            (object) [
                'id' => 0,
                'status' => (object) ['type' => 'Available'],
                'bookingAvailability' => (object) [
                    'capacity' => 100,
                    'remainingCapacity' => 42,
                ],
            ],
            (object) [
                'id' => 1,
                'bookingAvailability' => (object) [
                    'capacity' => 10,
                    'remainingCapacity' => 99,
                ],
            ],
        ];

        $request = $this->requestBuilder->build('PATCH')->withParsedBody($body);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/0/status', 'status and bookingAvailability.remainingCapacity are mutually exclusive'),
                new SchemaError('/1/bookingAvailability/remainingCapacity', 'remainingCapacity must be less than or equal to capacity')
            ),
            fn () => $this->parser->parse($request)
        );
    }

    /**
     * @test
     */
    public function it_passes_through_when_remainingCapacity_equals_capacity(): void
    {
        $body = [
            (object) [
                'id' => 0,
                'bookingAvailability' => (object) [
                    'capacity' => 100,
                    'remainingCapacity' => 100,
                ],
            ],
        ];

        $request = $this->requestBuilder->build('PATCH')->withParsedBody($body);

        $result = $this->parser->parse($request)->getParsedBody();

        $this->assertEquals($body, $result);
    }
}
