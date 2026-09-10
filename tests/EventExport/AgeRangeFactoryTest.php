<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport;

use PHPUnit\Framework\TestCase;

final class AgeRangeFactoryTest extends TestCase
{
    /**
     * @test
     * @dataProvider typicalAgeRanges
     */
    public function it_only_accepts_a_specific_age_range(mixed $typicalAgeRange, ?string $expected): void
    {
        $ageRange = AgeRangeFactory::specificFromString($typicalAgeRange);

        $this->assertSame($expected, $ageRange?->toString());
        $this->assertSame($expected !== null, AgeRangeFactory::hasSpecificAgeRange($typicalAgeRange));
    }

    public function typicalAgeRanges(): array
    {
        return [
            'a range' => ['typicalAgeRange' => '6-12', 'expected' => '6-12'],
            'an open ended range' => ['typicalAgeRange' => '6-', 'expected' => '6-'],
            'a single age' => ['typicalAgeRange' => '6-6', 'expected' => '6-6'],
            'up to an age' => ['typicalAgeRange' => '0-12', 'expected' => '0-12'],
            'all ages' => ['typicalAgeRange' => '-', 'expected' => null],
            // AgeRange::toString() also answers "-" for "0-", so the original string cannot be
            // compared to recognise an all ages event.
            'all ages written as 0-' => ['typicalAgeRange' => '0-', 'expected' => null],
            'not a range at all' => ['typicalAgeRange' => 'zes tot twaalf', 'expected' => null],
            'an empty string' => ['typicalAgeRange' => '', 'expected' => null],
            'not a string' => ['typicalAgeRange' => 12, 'expected' => null],
            'absent' => ['typicalAgeRange' => null, 'expected' => null],
        ];
    }
}
