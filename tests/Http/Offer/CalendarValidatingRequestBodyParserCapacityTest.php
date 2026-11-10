<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\TestCase;

final class CalendarValidatingRequestBodyParserCapacityTest extends TestCase
{
    use AssertApiProblemTrait;

    private CalendarValidatingRequestBodyParser $parser;

    protected function setUp(): void
    {
        $this->parser = new CalendarValidatingRequestBodyParser();
    }

    /**
     * @test
     * @dataProvider calendarTypesWithoutCapacityProvider
     */
    public function it_rejects_capacity_on_calendar_types_without_capacity(object $data): void
    {
        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromObject($data)
            ->build('PUT')
            ->withParsedBody($data);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/subEvent/0/capacity', 'capacity is only allowed when calendarType is single or multiple')
            ),
            fn () => $this->parser->parse($request)
        );
    }

    public function calendarTypesWithoutCapacityProvider(): array
    {
        $subEvent = (object)[
            'startDate' => '2026-11-05T10:00:00+01:00',
            'endDate' => '2026-11-05T12:00:00+01:00',
            'capacity' => (object)['type' => 'Limited', 'maximum' => 30],
        ];

        return [
            'periodic' => [
                (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2026-11-01T00:00:00+01:00',
                    'endDate' => '2026-11-30T23:59:59+01:00',
                    'subEvent' => [$subEvent],
                ],
            ],
            'permanent' => [
                (object)[
                    'calendarType' => 'permanent',
                    'subEvent' => [$subEvent],
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_accepts_capacity_on_a_multiple_calendar(): void
    {
        $data = (object)[
            'calendarType' => 'multiple',
            'subEvent' => [
                (object)[
                    'startDate' => '2026-11-05T10:00:00+01:00',
                    'endDate' => '2026-11-05T12:00:00+01:00',
                    'capacity' => (object)['type' => 'Limited', 'maximum' => 30, 'taken' => 12],
                ],
                (object)[
                    'startDate' => '2026-11-06T10:00:00+01:00',
                    'endDate' => '2026-11-06T12:00:00+01:00',
                ],
            ],
        ];

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromObject($data)
            ->build('PUT')
            ->withParsedBody($data);

        $this->expectNotToPerformAssertions();
        $this->parser->parse($request);
    }
}
