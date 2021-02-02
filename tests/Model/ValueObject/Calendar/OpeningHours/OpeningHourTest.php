<?php

namespace CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours;

use PHPUnit\Framework\TestCase;

class OpeningHourTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_have_a_list_of_open_days_combined_with_an_opening_and_closing_time()
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
}
