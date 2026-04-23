<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Holidays;

use DateTimeImmutable;
use Symfony\Contracts\Cache\CacheInterface;

final class CachedHolidaysService implements HolidaysService
{
    public function __construct(
        private readonly HolidaysService $holidays,
        private readonly CacheInterface $cache
    ) {
    }

    public function getHolidays(DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        return $this->cache->get(
            $this->createCacheKey($startDate, $endDate),
            fn () => $this->holidays->getHolidays($startDate, $endDate)
        );
    }

    private function createCacheKey(DateTimeImmutable $startDate, DateTimeImmutable $endDate): string
    {
        return 'holidays_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d');
    }
}
