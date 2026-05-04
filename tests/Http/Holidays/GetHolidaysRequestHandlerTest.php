<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Holidays;

use CultuurNet\UDB3\Clock\Clock;
use CultuurNet\UDB3\Holidays\HolidaysService;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetHolidaysRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;
    use AssertJsonResponseTrait;

    private HolidaysService&MockObject $holidaysService;
    private Clock&MockObject $clock;
    private GetHolidaysRequestHandler $handler;

    protected function setUp(): void
    {
        $this->holidaysService = $this->createMock(HolidaysService::class);
        $this->clock = $this->createMock(Clock::class);
        $this->clock->method('getDateTime')->willReturn(new DateTimeImmutable('2025-01-15'));
        $this->handler = new GetHolidaysRequestHandler($this->holidaysService, $this->clock);
    }

    /**
     * @test
     */
    public function it_returns_holidays_with_default_date_range(): void
    {
        $expectedHolidays = [
            [
                'startDate' => '2025-01-01',
                'endDate' => '2025-01-01',
                'type' => 'holidays',
                'name' => [['language' => 'NL', 'text' => 'Nieuwjaarsdag']],
            ],
        ];

        $this->holidaysService
            ->expects($this->once())
            ->method('getHolidays')
            ->with(
                new DateTimeImmutable('2025-01-15 00:00:00'),
                new DateTimeImmutable('2026-01-15 00:00:00')
            )
            ->willReturn($expectedHolidays);

        $request = (new Psr7RequestBuilder())->build('GET');

        $response = $this->handler->handle($request);

        $this->assertJsonResponse(new JsonResponse($expectedHolidays), $response);
    }

    /**
     * @test
     */
    public function it_returns_holidays_with_explicit_date_range(): void
    {
        $expectedHolidays = [
            [
                'startDate' => '2025-03-01',
                'endDate' => '2025-03-10',
                'type' => 'schoolHolidays',
                'name' => [['language' => 'NL', 'text' => 'Krokusvakantie']],
            ],
        ];

        $this->holidaysService
            ->expects($this->once())
            ->method('getHolidays')
            ->with(
                new DateTimeImmutable('2025-03-01 00:00:00'),
                new DateTimeImmutable('2025-06-01 00:00:00')
            )
            ->willReturn($expectedHolidays);

        $request = (new Psr7RequestBuilder())
            ->withUriFromString('holidays?startDate=2025-03-01&endDate=2025-06-01')
            ->build('GET');

        $response = $this->handler->handle($request);

        $this->assertJsonResponse(new JsonResponse($expectedHolidays), $response);
    }

    /**
     * @test
     */
    public function it_throws_when_end_date_exceeds_5_years_in_the_future(): void
    {
        $this->holidaysService->expects($this->never())->method('getHolidays');

        $request = (new Psr7RequestBuilder())
            ->withUriFromString('holidays?endDate=2030-01-16')
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::dateRangeExceedsLimit(),
            fn () => $this->handler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_on_invalid_start_date_format(): void
    {
        $this->holidaysService->expects($this->never())->method('getHolidays');

        $request = (new Psr7RequestBuilder())
            ->withUriFromString('holidays?startDate=not-a-date')
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::queryParameterInvalidValue('startDate', 'not-a-date', ['YYYY-MM-DD']),
            fn () => $this->handler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_when_start_date_is_after_end_date(): void
    {
        $this->holidaysService->expects($this->never())->method('getHolidays');

        $request = (new Psr7RequestBuilder())
            ->withUriFromString('holidays?startDate=2026-06-01&endDate=2025-01-01')
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::startDateCannotBeAfterEndDate(),
            fn () => $this->handler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_on_invalid_end_date_format(): void
    {
        $this->holidaysService->expects($this->never())->method('getHolidays');

        $request = (new Psr7RequestBuilder())
            ->withUriFromString('holidays?endDate=not-a-date')
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::queryParameterInvalidValue('endDate', 'not-a-date', ['YYYY-MM-DD']),
            fn () => $this->handler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_on_overflowing_start_date(): void
    {
        $this->holidaysService->expects($this->never())->method('getHolidays');

        $request = (new Psr7RequestBuilder())
            ->withUriFromString('holidays?startDate=2025-13-01')
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::queryParameterInvalidValue('startDate', '2025-13-01', ['YYYY-MM-DD']),
            fn () => $this->handler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_on_overflowing_end_date(): void
    {
        $this->holidaysService->expects($this->never())->method('getHolidays');

        $request = (new Psr7RequestBuilder())
            ->withUriFromString('holidays?endDate=2025-13-01')
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::queryParameterInvalidValue('endDate', '2025-13-01', ['YYYY-MM-DD']),
            fn () => $this->handler->handle($request)
        );
    }
}
