<?php

namespace CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours;

use PHPUnit\Framework\TestCase;

class HourTest extends TestCase
{
    /**
     * @test
     * @dataProvider invalidHourDataProvider
     *
     * @param int $invalidHour
     */
    public function it_should_not_be_lower_than_zero_or_higher_than_twenty_three($invalidHour)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Hour should be an integer between 0 and 23.');

        new Hour($invalidHour);
    }

    /**
     * @return array
     */
    public function invalidHourDataProvider()
    {
        return [
            'negative' => [
                -1,
            ],
            'over_twenty_three' => [
                24,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider validHourDataProvider
     *
     * @param int $validHour
     */
    public function it_should_be_between_zero_and_twenty_three($validHour)
    {
        $hour = new Hour($validHour);
        $this->assertEquals($validHour, $hour->toInteger());
    }

    /**
     * @return array
     */
    public function validHourDataProvider()
    {
        return array_map(
            function ($hour) {
                return [$hour];
            },
            range(0, 23)
        );
    }
}
