<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours;

use PHPUnit\Framework\TestCase;

class TimeTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_combine_an_hour_and_minute()
    {
        $hour = new Hour(12);
        $minute = new Minute(24);
        $time = new Time($hour, $minute);

        $this->assertEquals($hour, $time->getHour());
        $this->assertEquals($minute, $time->getMinute());
    }
}
