<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport;

use CultuurNet\UDB3\Json;
use PHPUnit\Framework\TestCase;

final class BirthdateRangeFactoryTest extends TestCase
{
    /**
     * @test
     * @dataProvider birthdateRanges
     */
    public function it_only_accepts_a_real_range(?array $birthdateRange, ?string $expected): void
    {
        $range = BirthdateRangeFactory::fromJson(
            $birthdateRange === null ? null : Json::decode(Json::encode($birthdateRange))
        );

        if ($expected === null) {
            $this->assertNull($range);
            return;
        }

        $this->assertSame($expected, BirthdateRangeFactory::formatRange($range));
    }

    public function birthdateRanges(): array
    {
        return [
            'a range' => [
                'birthdateRange' => ['from' => '2010-01-01', 'to' => '2010-12-31'],
                'expected' => '01/01/2010 - 31/12/2010',
            ],
            'a single day' => [
                'birthdateRange' => ['from' => '2010-01-01', 'to' => '2010-01-01'],
                'expected' => '01/01/2010 - 01/01/2010',
            ],
            'no birthdate range at all' => [
                'birthdateRange' => null,
                'expected' => null,
            ],
            'without a to' => [
                'birthdateRange' => ['from' => '2010-01-01'],
                'expected' => null,
            ],
            'without a from' => [
                'birthdateRange' => ['to' => '2010-12-31'],
                'expected' => null,
            ],
            'a date that rolls over' => [
                'birthdateRange' => ['from' => '2010-13-45', 'to' => '2010-12-31'],
                'expected' => null,
            ],
            'a date that is not a string' => [
                'birthdateRange' => ['from' => 2010, 'to' => '2010-12-31'],
                'expected' => null,
            ],
            'a to that precedes the from' => [
                'birthdateRange' => ['from' => '2010-12-31', 'to' => '2010-01-01'],
                'expected' => null,
            ],
        ];
    }
}
