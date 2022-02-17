<?php

declare(strict_types=1);

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
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
                'saturday',
                'sunday',
            ],
            DayOfWeek::getAllowedValues()
        );
    }
}
