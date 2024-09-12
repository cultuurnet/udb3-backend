<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours;

use PHPUnit\Framework\TestCase;

class MinuteTest extends TestCase
{
    /**
     * @test
     * @dataProvider invalidMinuteDataProvider
     *
     * @param int $invalidMinute
     */
    public function it_should_not_be_lower_than_zero_or_higher_than_fifty_nine($invalidMinute): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Minute should be an integer between 0 and 59.');

        new Minute($invalidMinute);
    }

    public function invalidMinuteDataProvider(): array
    {
        return [
            'negative' => [
                -1,
            ],
            'over_fifty_nine' => [
                60,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider validMinuteDataProvider
     *
     * @param int $validMinute
     */
    public function it_should_be_between_zero_and_fifty_nine($validMinute): void
    {
        $minute = new Minute($validMinute);
        $this->assertEquals($validMinute, $minute->toInteger());
    }

    public function validMinuteDataProvider(): array
    {
        return array_map(
            function ($minute) {
                return [$minute];
            },
            range(0, 59)
        );
    }
}
