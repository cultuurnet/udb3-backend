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
    public function it_passes_through_a_valid_body_with_availability_and_capacity(): void
    {
        $body = [
            (object) [
                'id' => 0,
                'bookingAvailability' => (object) [
                    'capacity' => 100,
                    'availability' => 42,
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
    public function it_passes_through_a_valid_body_with_status_but_no_availability(): void
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
    public function it_throws_when_status_and_availability_are_both_present(): void
    {
        $body = [
            (object) [
                'id' => 0,
                'status' => (object) ['type' => 'Available'],
                'bookingAvailability' => (object) [
                    'capacity' => 100,
                    'availability' => 42,
                ],
            ],
        ];

        $request = $this->requestBuilder->build('PATCH')->withParsedBody($body);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/0/status', 'status and bookingAvailability.availability are mutually exclusive')
            ),
            fn () => $this->parser->parse($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_when_availability_exceeds_capacity(): void
    {
        $body = [
            (object) [
                'id' => 0,
                'bookingAvailability' => (object) [
                    'capacity' => 50,
                    'availability' => 100,
                ],
            ],
        ];

        $request = $this->requestBuilder->build('PATCH')->withParsedBody($body);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/0/bookingAvailability/availability', 'availability must be less than or equal to capacity')
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
                    'availability' => 42,
                ],
            ],
            (object) [
                'id' => 1,
                'bookingAvailability' => (object) [
                    'capacity' => 10,
                    'availability' => 99,
                ],
            ],
        ];

        $request = $this->requestBuilder->build('PATCH')->withParsedBody($body);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/0/status', 'status and bookingAvailability.availability are mutually exclusive'),
                new SchemaError('/1/bookingAvailability/availability', 'availability must be less than or equal to capacity')
            ),
            fn () => $this->parser->parse($request)
        );
    }

    /**
     * @test
     */
    public function it_passes_through_when_availability_equals_capacity(): void
    {
        $body = [
            (object) [
                'id' => 0,
                'bookingAvailability' => (object) [
                    'capacity' => 100,
                    'availability' => 100,
                ],
            ],
        ];

        $request = $this->requestBuilder->build('PATCH')->withParsedBody($body);

        $result = $this->parser->parse($request)->getParsedBody();

        $this->assertEquals($body, $result);
    }
}
