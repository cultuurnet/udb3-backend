<?php

namespace CultuurNet\UDB3\Calendar;

use PHPUnit\Framework\TestCase;

class DayOfWeekTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_all_the_days_of_a_week_in_lower_case()
    {
        $this->assertEquals(
            [
                'MONDAY' => 'monday',
                'TUESDAY' => 'tuesday',
                'WEDNESDAY' => 'wednesday',
                'THURSDAY' => 'thursday',
                'FRIDAY' => 'friday',
                'SATURDAY' => 'saturday',
                'SUNDAY' => 'sunday',
            ],
            DayOfWeek::getConstants()
        );
    }
}
