<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport;

use PHPUnit\Framework\TestCase;

final class BirthdateRangeFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_a_range_from_two_birthdates(): void
    {
        $range = BirthdateRangeFactory::fromJson((object) ['from' => '2026-01-01', 'to' => '2026-08-27']);

        $this->assertNotNull($range);
        $this->assertSame('2026-01-01', $range->getFrom()->format('Y-m-d'));
        $this->assertSame('2026-08-27', $range->getTo()->format('Y-m-d'));
    }

    /**
     * @test
     */
    public function it_resets_the_time_of_a_birthdate(): void
    {
        $range = BirthdateRangeFactory::fromJson((object) ['from' => '2026-01-01', 'to' => '2026-01-01']);

        $this->assertNotNull($range);
        $this->assertSame('00:00:00', $range->getFrom()->format('H:i:s'));
        $this->assertSame('00:00:00', $range->getTo()->format('H:i:s'));
    }

    /**
     * @test
     * @dataProvider unusableBirthdateRanges
     */
    public function it_does_not_create_a_range_from_an_unusable_value(mixed $birthdateRange): void
    {
        $this->assertNull(BirthdateRangeFactory::fromJson($birthdateRange));
    }

    public function unusableBirthdateRanges(): array
    {
        return [
            'no birthdate range' => [null],
            'not an object' => ['2026-01-01/2026-08-27'],
            'an empty object' => [(object) []],
            'without a from' => [(object) ['to' => '2026-08-27']],
            'without a to' => [(object) ['from' => '2026-01-01']],
            'a non string birthdate' => [(object) ['from' => 2026, 'to' => '2026-08-27']],
            'an empty birthdate' => [(object) ['from' => '', 'to' => '']],
            'a birthdate that is not a date' => [(object) ['from' => '2026-01-01', 'to' => 'gisteren']],
            'a birthdate in another format' => [(object) ['from' => '01/01/2026', 'to' => '27/08/2026']],
            'an out of range birthdate' => [(object) ['from' => '2026-13-45', 'to' => '2026-08-27']],
            'a from after the to' => [(object) ['from' => '2026-08-27', 'to' => '2026-01-01']],
        ];
    }
}
