<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\TestCase;

final class CapacityValidatingRequestBodyParserTest extends TestCase
{
    use AssertApiProblemTrait;

    private CapacityValidatingRequestBodyParser $parser;

    private Psr7RequestBuilder $requestBuilder;

    protected function setUp(): void
    {
        $this->parser = new CapacityValidatingRequestBodyParser();
        $this->requestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     */
    public function it_returns_the_request_unchanged_when_body_is_not_an_object(): void
    {
        $request = $this->requestBuilder
            ->build('POST')
            ->withParsedBody(['calendarType' => 'permanent']);

        $this->assertSame($request, $this->parser->parse($request));
    }

    /**
     * @test
     * @dataProvider subEventCalendarTypeProvider
     */
    public function it_allows_capacity_on_single_or_multiple_calendars(string $calendarType): void
    {
        $body = (object) [
            'calendarType' => $calendarType,
            'bookingAvailability' => (object) [
                'type' => 'Available',
                'capacity' => 100,
            ],
        ];

        $request = $this->requestBuilder
            ->build('POST')
            ->withParsedBody($body);

        $this->assertSame($request, $this->parser->parse($request));
    }

    public function subEventCalendarTypeProvider(): array
    {
        return [
            'single' => ['single'],
            'multiple' => ['multiple'],
        ];
    }

    /**
     * @test
     */
    public function it_returns_the_request_unchanged_when_bookingAvailability_is_missing(): void
    {
        $body = (object) [
            'calendarType' => 'permanent',
        ];

        $request = $this->requestBuilder
            ->build('POST')
            ->withParsedBody($body);

        $this->assertSame($request, $this->parser->parse($request));
    }

    /**
     * @test
     */
    public function it_returns_the_request_unchanged_when_permanent_calendar_has_no_capacity(): void
    {
        $body = (object) [
            'calendarType' => 'permanent',
            'bookingAvailability' => (object) [
                'type' => 'Available',
            ],
        ];

        $request = $this->requestBuilder
            ->build('POST')
            ->withParsedBody($body);

        $this->assertSame($request, $this->parser->parse($request));
    }

    /**
     * @test
     * @dataProvider nonSubEventCalendarTypeProvider
     */
    public function it_throws_when_capacity_is_set_on_a_permanent_or_periodic_calendar(string $calendarType): void
    {
        $body = (object) [
            'calendarType' => $calendarType,
            'bookingAvailability' => (object) [
                'type' => 'Available',
                'capacity' => 100,
            ],
        ];

        $request = $this->requestBuilder
            ->build('POST')
            ->withParsedBody($body);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/bookingAvailability/capacity',
                    'capacity is not supported on events with a permanent or periodic calendar.'
                )
            ),
            fn () => $this->parser->parse($request)
        );
    }

    public function nonSubEventCalendarTypeProvider(): array
    {
        return [
            'permanent' => ['permanent'],
            'periodic' => ['periodic'],
        ];
    }

    /**
     * @test
     */
    public function it_throws_when_capacity_is_null_on_a_permanent_calendar(): void
    {
        $body = (object) [
            'calendarType' => 'permanent',
            'bookingAvailability' => (object) [
                'type' => 'Available',
                'capacity' => null,
            ],
        ];

        $request = $this->requestBuilder
            ->build('POST')
            ->withParsedBody($body);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/bookingAvailability/capacity',
                    'capacity is not supported on events with a permanent or periodic calendar.'
                )
            ),
            fn () => $this->parser->parse($request)
        );
    }
}
