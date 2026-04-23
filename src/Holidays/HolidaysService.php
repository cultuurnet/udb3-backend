<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Holidays;

use DateTimeImmutable;

interface HolidaysService
{
    /**
     * @return array<array{startDate: string, endDate: string, type: string, name: array<array{language: string, text: string}>}>
     */
    public function getHolidays(DateTimeImmutable $startDate, DateTimeImmutable $endDate): array;
}
