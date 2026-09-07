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
     * @test
     */
    public function it_does_not_describe_a_range_with_a_from_after_the_to(): void
    {
        $this->assertNull(
            BirthdateRangeDescription::fromBirthdateRange(
                (object) ['from' => '2026-08-27', 'to' => '2026-01-01']
            )
        );
    }

    /**
     * @test
     */
    public function it_does_not_describe_a_range_without_a_from(): void
    {
        $this->assertNull(
            BirthdateRangeDescription::fromBirthdateRange((object) ['to' => '2026-08-27'])
        );
    }

    /**
     * @test
     */
    public function it_does_not_describe_a_range_without_a_to(): void
    {
        $this->assertNull(
            BirthdateRangeDescription::fromBirthdateRange((object) ['from' => '2026-01-01'])
        );
    }

    /**
     * @test
     */
    public function it_does_not_describe_a_range_with_a_non_string_birthdate(): void
    {
        $this->assertNull(
            BirthdateRangeDescription::fromBirthdateRange(
                (object) ['from' => 2026, 'to' => '2026-08-27']
            )
        );
    }

    /**
     * @test
     */
    public function it_does_not_describe_a_range_with_an_out_of_range_birthdate(): void
    {
        $this->assertNull(
            BirthdateRangeDescription::fromBirthdateRange(
                (object) ['from' => '2026-13-45', 'to' => '2026-08-27']
            )
        );
    }

    /**
     * @test
     */
    public function it_does_not_describe_a_range_with_a_birthdate_that_is_not_a_date(): void
    {
        $this->assertNull(
            BirthdateRangeDescription::fromBirthdateRange(
                (object) ['from' => '2026-01-01', 'to' => 'gisteren']
            )
        );
    }

    /**
     * @test
     */
    public function it_does_not_describe_a_range_with_a_birthdate_in_another_format(): void
    {
        $this->assertNull(
            BirthdateRangeDescription::fromBirthdateRange(
                (object) ['from' => '01/01/2026', 'to' => '27/08/2026']
            )
        );
    }

    /**
     * @test
     */
    public function it_does_not_describe_a_range_with_an_empty_birthdate(): void
    {
        $this->assertNull(
            BirthdateRangeDescription::fromBirthdateRange(
                (object) ['from' => '', 'to' => '']
            )
        );
    }

    /**
     * @test
     */
    public function it_does_not_describe_an_empty_range(): void
    {
        $this->assertNull(BirthdateRangeDescription::fromBirthdateRange((object) []));
    }
}
