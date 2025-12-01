<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Audience;

use PHPUnit\Framework\TestCase;

class AgeRangeTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_never_have_a_from_greater_than_the_to(): void
    {
        $from = new Age(10);
        $to = new Age(8);

        $this->expectException(InvalidAgeRangeException::class);
        $this->expectExceptionMessage('"From" age should not be greater than the "to" age.');

        new AgeRange($from, $to);
    }

    /**
     * @test
     */
    public function it_should_return_the_given_from_and_to(): void
    {
        $from = new Age(10);
        $to = new Age(18);
        $range = new AgeRange($from, $to);

        $this->assertEquals($from, $range->getFrom());
        $this->assertEquals($to, $range->getTo());
    }

    /**
     * @test
     */
    public function it_should_be_creatable_with_just_a_from(): void
    {
        $from = new Age(10);
        $range = AgeRange::from($from);

        $this->assertEquals($from, $range->getFrom());
        $this->assertNull($range->getTo());
    }

    /**
     * @test
     */
    public function it_should_be_creatable_with_just_a_to(): void
    {
        $to = new Age(10);
        $range = AgeRange::to($to);

        $this->assertEquals(new Age(0), $range->getFrom());
        $this->assertEquals($to, $range->getTo());
    }

    /**
     * @test
     */
    public function it_should_be_creatable_with_from_and_to(): void
    {
        $from = new Age(10);
        $to = new Age(18);
        $range = AgeRange::fromTo($from, $to);

        $this->assertEquals($from, $range->getFrom());
        $this->assertEquals($to, $range->getTo());
    }

    /**
     * @test
     */
    public function it_should_be_creatable_with_exactly_one_age(): void
    {
        $age = new Age(10);
        $range = AgeRange::exactly($age);

        $this->assertEquals($age, $range->getFrom());
        $this->assertEquals($age, $range->getTo());
    }

    /**
     * @test
     */
    public function it_should_be_creatable_with_any_age(): void
    {
        $range = AgeRange::any();

        $this->assertNull($range->getFrom());
        $this->assertNull($range->getTo());
    }

    /**
     * @test
     * @dataProvider ageRangeStringProvider
     */
    public function it_should_create_ranges_from_strings(
        string $ageRangeString,
        AgeRange $expectedRange
    ): void {
        $ageRange = AgeRange::fromString($ageRangeString);

        $this->assertEquals($expectedRange, $ageRange);
    }

    public function ageRangeStringProvider(): array
    {
        return [
            'ALL' =>
                [
                    'ageRangeString' => '-',
                    'expectedRange' => new AgeRange(),
                    'expectedRangeString' => '-',
                ],
            'ALL_ZERO' =>
                [
                    'ageRangeString' => '0-',
                    'expectedRange' => new AgeRange(new Age(0)),
                    'expectedRangeString' => '-',
                ],
            'TODDLERS' =>
                [
                    'ageRangeString' => '0-2',
                    'expectedRange' => new AgeRange(new Age(0), new Age(2)),
                    'expectedRangeString' => '0-2',
                ],
            'PRESCHOOLERS' =>
                [
                    'ageRangeString' => '3-5',
                    'expectedRange' => new AgeRange(new Age(3), new Age(5)),
                    'expectedRangeString' => '3-5',
                ],
            'KIDS' =>
                [
                    'ageRangeString' => '6-11',
                    'expectedRange' => new AgeRange(new Age(6), new Age(11)),
                    'expectedRangeString' => '6-11',
                ],
            'YOUNGSTERS' =>
                [
                    'ageRangeString' => '12-17',
                    'expectedRange' => new AgeRange(new Age(12), new Age(17)),
                    'expectedRangeString' => '12-17',
                ],
            'ADULTS' =>
                [
                    'ageRangeString' => '18-',
                    'expectedRange' => new AgeRange(new Age(18)),
                    'expectedRangeString' => '18-',
                ],
            'SENIORS' =>
                [
                    'ageRangeString' => '65-',
                    'expectedRange' => new AgeRange(new Age(65)),
                    'expectedRangeString' => '65-',
                ],
            'CUSTOM' =>
                [
                    'ageRangeString' => '5-55',
                    'expectedRange' => new AgeRange(new Age(5), new Age(55)),
                    'expectedRangeString' => '5-55',
                ],
            'EIGHTEEN' =>
                [
                    'ageRangeString' => '18-18',
                    'expectedRange' => new AgeRange(new Age(18), new Age(18)),
                    'expectedRangeString' => '18-18',
                ],
        ];
    }

    /**
     * @test
     * @dataProvider invalidAgeRangeStringProvider
     */
    public function it_should_throw_an_exception_on_unexpected_age_range_strings(
        string $ageRangeString,
        string $exception,
        string $exceptionMessage
    ): void {
        $this->expectException($exception);
        $this->expectExceptionMessage($exceptionMessage);
        AgeRange::fromString($ageRangeString);
    }

    public function invalidAgeRangeStringProvider(): array
    {
        return [
            'dat boi' => [
                'ageRangeString' => '🐸-🚲',
                'exception' => InvalidAgeRangeException::class,
                'The "from" age should be a natural number or empty.',
            ],
            'limitless' => [
                'ageRangeString' => '9999999',
                'exception' => InvalidAgeRangeException::class,
                'Date-range string is not valid because it is missing a hyphen.',
            ],
            'words' => [
                'ageRangeString' => '1 to 18',
                'exception' => InvalidAgeRangeException::class,
                'Date-range string is not valid because it is missing a hyphen.',
            ],
            'en dash' => [
                'ageRangeString' => '1–18',
                'exception' => InvalidAgeRangeException::class,
                'Date-range string is not valid because it is missing a hyphen.',
            ],
            'horizontal bar' => [
                'ageRangeString' => '1―18',
                'exception' => InvalidAgeRangeException::class,
                'Date-range string is not valid because it is missing a hyphen.',
            ],
            'tilde' => [
                'ageRangeString' => '1~18',
                'exception' => InvalidAgeRangeException::class,
                'Date-range string is not valid because it is missing a hyphen.',
            ],
            'triple trouble' => [
                'ageRangeString' => '1---18',
                'exception' => InvalidAgeRangeException::class,
                'Date-range string is not valid because it has too many hyphens.',
            ],
            '😐' => [
                'ageRangeString' => '----',
                'exception' => InvalidAgeRangeException::class,
                'Date-range string is not valid because it has too many hyphens.',
            ],
            'non numeric upper-bound' => [
                'ageRangeString' => '0-Z',
                'exception' => InvalidAgeRangeException::class,
                'The "to" age should be a natural number or empty.',
            ],
        ];
    }
}
