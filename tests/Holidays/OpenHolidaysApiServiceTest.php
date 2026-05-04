<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Holidays;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use DateTimeImmutable;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class OpenHolidaysApiServiceTest extends TestCase
{
    use AssertApiProblemTrait;

    private MockHandler $mockHandler;
    private OpenHolidaysApiService $service;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $this->service = new OpenHolidaysApiService(
            new Client(['handler' => $handlerStack]),
            new NullLogger()
        );
    }

    /**
     * @test
     */
    public function it_returns_merged_holidays_sorted_by_start_date(): void
    {
        $publicHolidaysBody = json_encode([
            [
                'startDate' => '2025-07-21',
                'endDate' => '2025-07-21',
                'name' => [['language' => 'NL', 'text' => 'Nationale feestdag']],
            ],
            [
                'startDate' => '2025-01-01',
                'endDate' => '2025-01-01',
                'name' => [['language' => 'NL', 'text' => 'Nieuwjaarsdag']],
            ],
        ], JSON_THROW_ON_ERROR);

        $schoolHolidaysBody = json_encode([
            [
                'startDate' => '2025-04-07',
                'endDate' => '2025-04-20',
                'name' => [['language' => 'NL', 'text' => 'Paasvakantie']],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->mockHandler->append(
            new Response(200, [], $publicHolidaysBody),
            new Response(200, [], $schoolHolidaysBody)
        );

        $result = $this->service->getHolidays(
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2025-12-31')
        );

        $this->assertCount(3, $result);

        $this->assertSame('2025-01-01', $result[0]['startDate']);
        $this->assertSame('holidays', $result[0]['type']);

        $this->assertSame('2025-04-07', $result[1]['startDate']);
        $this->assertSame('schoolHolidays', $result[1]['type']);

        $this->assertSame('2025-07-21', $result[2]['startDate']);
        $this->assertSame('holidays', $result[2]['type']);
    }

    /**
     * @test
     */
    public function it_throws_bad_gateway_on_non_200_response(): void
    {
        $this->mockHandler->append(
            new Response(503, [], 'Service Unavailable')
        );

        $this->assertCallableThrowsApiProblem(
            ApiProblem::badGateway('OpenHolidays API returned a non-200 status.'),
            fn () => $this->service->getHolidays(
                new DateTimeImmutable('2025-01-01'),
                new DateTimeImmutable('2025-12-31')
            )
        );
    }

    /**
     * @test
     */
    public function it_throws_bad_gateway_on_client_exception(): void
    {
        $this->mockHandler->append(
            new ConnectException('Connection refused', new Request('GET', '/PublicHolidays'))
        );

        $this->assertCallableThrowsApiProblem(
            ApiProblem::badGateway('Unable to reach the OpenHolidays API.'),
            fn () => $this->service->getHolidays(
                new DateTimeImmutable('2025-01-01'),
                new DateTimeImmutable('2025-12-31')
            )
        );
    }
}
