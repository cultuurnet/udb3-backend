<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours;

use CultuurNet\UDB3\Model\ValueObject\TimeImmutableRange;
use PHPUnit\Framework\TestCase;

class OpeningHourTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_have_a_list_of_open_days_combined_with_an_opening_and_closing_time(): void
    {
        $days = new Days(
            Day::monday(),
            Day::tuesday(),
            Day::wednesday()
        );

        $openingTime = new Time(
            new Hour(9),
            new Minute(0)
        );

        $closingTime = new Time(
            new Hour(12),
            new Minute(0)
        );

        $openingHour = new OpeningHour($days, $openingTime, $closingTime);

        $this->assertEquals($days, $openingHour->getDays());
        $this->assertEquals($openingTime, $openingHour->getOpeningTime());
        $this->assertEquals($closingTime, $openingHour->getClosingTime());
    }

    /**
     * @test
     */
    public function it_has_no_childcare_without_a_childcare_time_range(): void
    {
        $this->assertFalse($this->openingHour()->hasChildcare());
    }

    /**
     * @test
     */
    public function it_has_childcare_with_a_childcare_time_range(): void
    {
        $openingHour = $this->openingHour()->withChildcareTimeRange(
            new TimeImmutableRange(Time::fromString('08:00'), Time::fromString('18:00'))
        );

        $this->assertTrue($openingHour->hasChildcare());
    }

    /**
     * @test
     */
    public function it_has_no_childcare_with_an_empty_childcare_time_range(): void
    {
        $openingHour = $this->openingHour()->withChildcareTimeRange(new TimeImmutableRange());

        $this->assertFalse($openingHour->hasChildcare());
    }

    private function openingHour(): OpeningHour
    {
        return new OpeningHour(new Days(Day::monday()), Time::fromString('09:00'), Time::fromString('17:00'));
    }
}
