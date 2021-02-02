<?php

namespace CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours;

use PHPUnit\Framework\TestCase;

class DayTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_have_a_value_for_each_day_of_the_week()
    {
        $monday = Day::monday();
        $tuesday = Day::tuesday();
        $wednesday = Day::wednesday();
        $thursday = Day::thursday();
        $friday = Day::friday();
        $saturday = Day::saturday();
        $sunday = Day::sunday();

        $this->assertEquals('monday', $monday->toString());
        $this->assertEquals('tuesday', $tuesday->toString());
        $this->assertEquals('wednesday', $wednesday->toString());
        $this->assertEquals('thursday', $thursday->toString());
        $this->assertEquals('friday', $friday->toString());
        $this->assertEquals('saturday', $saturday->toString());
        $this->assertEquals('sunday', $sunday->toString());
    }
}
