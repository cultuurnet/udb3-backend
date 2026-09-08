<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\Properties;

use PHPUnit\Framework\TestCase;

final class BirthdateRangeDescriptionTest extends TestCase
{
    /**
     * @test
     */
    public function it_describes_a_range_between_two_birthdates(): void
    {
        $this->assertSame(
            'Geschikt voor mensen geboren tussen 01/01/2026 en 27/08/2026',
            BirthdateRangeDescription::fromBirthdateRange(
                (object) ['from' => '2026-01-01', 'to' => '2026-08-27']
            )
        );
    }

    /**
     * @test
     */
    public function it_describes_a_range_of_a_single_day(): void
    {
        $this->assertSame(
            'Geschikt voor mensen geboren op 01/01/2026',
            BirthdateRangeDescription::fromBirthdateRange(
                (object) ['from' => '2026-01-01', 'to' => '2026-01-01']
            )
        );
    }

    /**
     * Every way a value can fail to be a range is covered by BirthdateRangeFactoryTest, so this
     * only asserts that there is no sentence to describe when the factory hands back nothing.
     *
     * @test
     */
    public function it_does_not_describe_a_value_that_is_not_a_range(): void
    {
        $this->assertNull(BirthdateRangeDescription::fromBirthdateRange((object) []));
    }
}
