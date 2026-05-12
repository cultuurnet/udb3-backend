<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Holidays;

use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

final class CachedHolidaysServiceTest extends TestCase
{
    private HolidaysService&MockObject $innerService;
    private CacheInterface&MockObject $cache;
    private CachedHolidaysService $cachedService;

    protected function setUp(): void
    {
        $this->innerService = $this->createMock(HolidaysService::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->cachedService = new CachedHolidaysService($this->innerService, $this->cache);
    }

    /**
     * @test
     */
    public function it_caches_holidays_by_date_range(): void
    {
        $startDate = new DateTimeImmutable('2025-01-01');
        $endDate = new DateTimeImmutable('2025-12-31');

        $expectedHolidays = [
            [
                'startDate' => '2025-01-01',
                'endDate' => '2025-01-01',
                'type' => 'holidays',
                'name' => [['language' => 'NL', 'text' => 'Nieuwjaarsdag']],
            ],
        ];

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with('2025-01-01_2025-12-31', $this->anything())
            ->willReturn($expectedHolidays);

        $result = $this->cachedService->getHolidays($startDate, $endDate);

        $this->assertEquals($expectedHolidays, $result);
    }

    /**
     * @test
     */
    public function it_uses_a_unique_cache_key_per_date_range(): void
    {
        $startDate1 = new DateTimeImmutable('2025-01-01');
        $endDate1 = new DateTimeImmutable('2025-06-30');
        $startDate2 = new DateTimeImmutable('2025-07-01');
        $endDate2 = new DateTimeImmutable('2025-12-31');

        $capturedKeys = [];

        $this->cache
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(function (string $key, callable $callback) use (&$capturedKeys) {
                $capturedKeys[] = $key;
                return $callback(null);
            });

        $this->innerService
            ->expects($this->exactly(2))
            ->method('getHolidays')
            ->willReturn([]);

        $this->cachedService->getHolidays($startDate1, $endDate1);
        $this->cachedService->getHolidays($startDate2, $endDate2);

        $this->assertEquals(['2025-01-01_2025-06-30', '2025-07-01_2025-12-31'], $capturedKeys);
    }

    /**
     * @test
     */
    public function it_calls_inner_service_on_cache_miss(): void
    {
        $startDate = new DateTimeImmutable('2025-01-01');
        $endDate = new DateTimeImmutable('2025-12-31');

        $expectedHolidays = [
            [
                'startDate' => '2025-07-21',
                'endDate' => '2025-07-21',
                'type' => 'holidays',
                'name' => [['language' => 'NL', 'text' => 'Nationale feestdag']],
            ],
        ];

        $this->cache
            ->method('get')
            ->willReturnCallback(function (string $key, callable $callback) {
                return $callback(null);
            });

        $this->innerService
            ->expects($this->once())
            ->method('getHolidays')
            ->with($startDate, $endDate)
            ->willReturn($expectedHolidays);

        $result = $this->cachedService->getHolidays($startDate, $endDate);

        $this->assertEquals($expectedHolidays, $result);
    }

    /**
     * @test
     */
    public function it_does_not_call_inner_service_on_cache_hit(): void
    {
        $startDate = new DateTimeImmutable('2025-01-01');
        $endDate = new DateTimeImmutable('2025-12-31');
        $cachedResult = [['startDate' => '2025-01-01', 'endDate' => '2025-01-01', 'type' => 'holidays', 'name' => []]];

        $this->cache
            ->method('get')
            ->willReturn($cachedResult);

        $this->innerService
            ->expects($this->never())
            ->method('getHolidays');

        $result = $this->cachedService->getHolidays($startDate, $endDate);
        $this->assertEquals($cachedResult, $result);
    }
}
