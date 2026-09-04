<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\Properties;

use PHPUnit\Framework\TestCase;

final class AgeRangeDescriptionTest extends TestCase
{
    /**
     * @test
     */
    public function it_describes_a_range_with_a_lower_and_an_upper_age(): void
    {
        $this->assertSame(
            'Geschikt voor 6 tot 12 jaar',
            AgeRangeDescription::fromTypicalAgeRange('6-12')
        );
    }

    /**
     * @test
     */
    public function it_describes_a_range_without_an_upper_age(): void
    {
        $this->assertSame(
            'Geschikt voor 6 jaar en ouder',
            AgeRangeDescription::fromTypicalAgeRange('6-')
        );
    }

    /**
     * @test
     */
    public function it_describes_a_range_without_a_lower_age(): void
    {
        $this->assertSame(
            'Geschikt tot 12 jaar',
            AgeRangeDescription::fromTypicalAgeRange('-12')
        );
    }

    /**
     * @test
     */
    public function it_describes_a_range_starting_at_zero_as_a_range_without_a_lower_age(): void
    {
        $this->assertSame(
            'Geschikt tot 12 jaar',
            AgeRangeDescription::fromTypicalAgeRange('0-12')
        );
    }

    /**
     * @test
     */
    public function it_does_not_describe_a_range_starting_at_zero_without_an_upper_age(): void
    {
        $this->assertNull(AgeRangeDescription::fromTypicalAgeRange('0-'));
    }

    /**
     * @test
     */
    public function it_describes_a_range_with_an_identical_lower_and_upper_age(): void
    {
        $this->assertSame(
            'Geschikt voor 6 jaar',
            AgeRangeDescription::fromTypicalAgeRange('6-6')
        );
    }

    /**
     * @test
     */
    public function it_describes_a_range_for_babies_only(): void
    {
        $this->assertSame(
            'Geschikt voor 0 jaar',
            AgeRangeDescription::fromTypicalAgeRange('0-0')
        );
    }

    /**
     * @test
     */
    public function it_describes_a_range_without_a_lower_age_and_an_upper_age_of_zero(): void
    {
        $this->assertSame(
            'Geschikt voor 0 jaar',
            AgeRangeDescription::fromTypicalAgeRange('-0')
        );
    }

    /**
     * @test
     */
    public function it_does_not_describe_a_range_for_all_ages(): void
    {
        $this->assertNull(AgeRangeDescription::fromTypicalAgeRange('-'));
    }

    /**
     * @test
     */
    public function it_does_not_describe_a_range_with_a_lower_age_above_the_upper_age(): void
    {
        $this->assertNull(AgeRangeDescription::fromTypicalAgeRange('12-6'));
    }

    /**
     * @test
     */
    public function it_does_not_describe_a_range_without_a_hyphen(): void
    {
        $this->assertNull(AgeRangeDescription::fromTypicalAgeRange('6'));
    }

    /**
     * @test
     */
    public function it_does_not_describe_a_range_with_too_many_hyphens(): void
    {
        $this->assertNull(AgeRangeDescription::fromTypicalAgeRange('6-12-18'));
    }

    /**
     * @test
     */
    public function it_does_not_describe_a_range_with_non_numeric_ages(): void
    {
        $this->assertNull(AgeRangeDescription::fromTypicalAgeRange('zes-twaalf'));
    }

    /**
     * @test
     */
    public function it_does_not_describe_an_empty_range(): void
    {
        $this->assertNull(AgeRangeDescription::fromTypicalAgeRange(''));
    }
}
