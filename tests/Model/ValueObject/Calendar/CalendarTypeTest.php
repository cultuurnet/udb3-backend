<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use PHPUnit\Framework\TestCase;

class CalendarTypeTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_have_exactly_four_values(): void
    {
        $single = CalendarType::single();
        $multiple = CalendarType::multiple();
        $periodic = CalendarType::periodic();
        $permanent = CalendarType::permanent();

        $this->assertEquals('single', $single->toString());
        $this->assertEquals('multiple', $multiple->toString());
        $this->assertEquals('periodic', $periodic->toString());
        $this->assertEquals('permanent', $permanent->toString());
    }
}
