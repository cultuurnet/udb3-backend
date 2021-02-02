<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use PHPUnit\Framework\TestCase;

final class DateRangeTest extends TestCase
{
    /**
     * @test
     */
    public function it_requires_a_start_date(): void
    {
        $this->expectException(\TypeError::class);
        new DateRange(null, new \DateTimeImmutable());
    }

    /**
     * @test
     */
    public function it_requires_an_end_date(): void
    {
        $this->expectException(\TypeError::class);
        new DateRange(new \DateTimeImmutable(), null);
    }
}
