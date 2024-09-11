<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours;

use PHPUnit\Framework\TestCase;

class HourTest extends TestCase
{
    /**
     * @test
     * @dataProvider invalidHourDataProvider
     */
    public function it_should_not_be_lower_than_zero_or_higher_than_twenty_three(int $invalidHour): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Hour should be an integer between 0 and 23.');

        new Hour($invalidHour);
    }

    public function invalidHourDataProvider(): array
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
     */
    public function it_should_be_between_zero_and_twenty_three(int $validHour): void
    {
        $hour = new Hour($validHour);
        $this->assertEquals($validHour, $hour->toInteger());
    }

    public function validHourDataProvider(): array
    {
        return array_map(
            function ($hour) {
                return [$hour];
            },
            range(0, 23)
        );
    }
}
