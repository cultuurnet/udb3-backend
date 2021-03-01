<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours;

use PHPUnit\Framework\TestCase;

class OpeningHoursTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_always_be_open_if_no_opening_hours_are_given()
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

        $openingHours = new OpeningHours($openingHour);
        $openingHoursAlwaysOpen = new OpeningHours();

        $this->assertFalse($openingHours->isAlwaysOpen());
        $this->assertTrue($openingHoursAlwaysOpen->isAlwaysOpen());
    }
}
