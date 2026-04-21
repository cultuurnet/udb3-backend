<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Audience;

use PHPUnit\Framework\TestCase;

class BirthYearRangeTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_never_have_a_from_greater_than_the_to(): void
    {
        $this->expectException(InvalidAgeRangeException::class);
        $this->expectExceptionMessage('"From" birth year should not be greater than the "to" birth year.');

        new BirthYearRange(2020, 2014);
    }

    /**
     * @test
     */
    public function it_should_return_the_given_from_and_to(): void
    {
        $range = new BirthYearRange(2014, 2020);

        $this->assertEquals(2014, $range->getFrom());
        $this->assertEquals(2020, $range->getTo());
    }

    /**
     * @test
     */
    public function it_should_be_creatable_with_just_a_from(): void
    {
        $range = new BirthYearRange(2014);

        $this->assertEquals(2014, $range->getFrom());
        $this->assertNull($range->getTo());
    }

    /**
     * @test
     */
    public function it_should_be_creatable_with_just_a_to(): void
    {
        $range = new BirthYearRange(null, 2020);

        $this->assertNull($range->getFrom());
        $this->assertEquals(2020, $range->getTo());
    }

    /**
     * @test
     */
    public function it_should_be_creatable_with_no_bounds(): void
    {
        $range = new BirthYearRange();

        $this->assertNull($range->getFrom());
        $this->assertNull($range->getTo());
    }

    /**
     * @test
     * @dataProvider birthYearRangeStringProvider
     */
    public function it_should_create_ranges_from_strings(
        string $birthYearRangeString,
        BirthYearRange $expectedRange
    ): void {
        $birthYearRange = BirthYearRange::fromString($birthYearRangeString);

        $this->assertEquals($expectedRange, $birthYearRange);
    }

    public function birthYearRangeStringProvider(): array
    {
        return [
            'ALL' => [
                'birthYearRangeString' => '-',
                'expectedRange' => new BirthYearRange(),
            ],
            'RANGE' => [
                'birthYearRangeString' => '2014-2020',
                'expectedRange' => new BirthYearRange(2014, 2020),
            ],
            'FROM_ONLY' => [
                'birthYearRangeString' => '2014-',
                'expectedRange' => new BirthYearRange(2014),
            ],
            'TO_ONLY' => [
                'birthYearRangeString' => '-2020',
                'expectedRange' => new BirthYearRange(null, 2020),
            ],
            'EXACT' => [
                'birthYearRangeString' => '2015-2015',
                'expectedRange' => new BirthYearRange(2015, 2015),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider birthYearRangeToStringProvider
     */
    public function it_should_convert_to_string(
        BirthYearRange $range,
        string $expectedString
    ): void {
        $this->assertEquals($expectedString, $range->toString());
    }

    public function birthYearRangeToStringProvider(): array
    {
        return [
            'RANGE' => [
                'range' => new BirthYearRange(2014, 2020),
                'expectedString' => '2014-2020',
            ],
            'FROM_ONLY' => [
                'range' => new BirthYearRange(2014),
                'expectedString' => '2014-',
            ],
            'TO_ONLY' => [
                'range' => new BirthYearRange(null, 2020),
                'expectedString' => '-2020',
            ],
            'ALL' => [
                'range' => new BirthYearRange(),
                'expectedString' => '-',
            ],
        ];
    }

    /**
     * @test
     */
    public function it_should_compare_two_ranges(): void
    {
        $range = new BirthYearRange(2014, 2020);
        $sameRange = new BirthYearRange(2014, 2020);
        $differentRange = new BirthYearRange(2015, 2021);

        $this->assertTrue($range->sameAs($sameRange));
        $this->assertFalse($range->sameAs($differentRange));
    }

    /**
     * @test
     * @dataProvider invalidBirthYearRangeStringProvider
     */
    public function it_should_throw_an_exception_on_unexpected_birth_year_range_strings(
        string $birthYearRangeString,
        string $exception,
        string $exceptionMessage
    ): void {
        $this->expectException($exception);
        $this->expectExceptionMessage($exceptionMessage);
        BirthYearRange::fromString($birthYearRangeString);
    }

    public function invalidBirthYearRangeStringProvider(): array
    {
        return [
            'no hyphen' => [
                'birthYearRangeString' => '2014',
                'exception' => InvalidAgeRangeException::class,
                'exceptionMessage' => 'Birth year range string is not valid because it is missing a hyphen.',
            ],
            'too many hyphens' => [
                'birthYearRangeString' => '2014--2020',
                'exception' => InvalidAgeRangeException::class,
                'exceptionMessage' => 'Birth year range string is not valid because it has too many hyphens.',
            ],
            'non numeric from' => [
                'birthYearRangeString' => 'abc-2020',
                'exception' => InvalidAgeRangeException::class,
                'exceptionMessage' => 'The "from" birth year should be a natural number or empty.',
            ],
            'non numeric to' => [
                'birthYearRangeString' => '2014-abc',
                'exception' => InvalidAgeRangeException::class,
                'exceptionMessage' => 'The "to" birth year should be a natural number or empty.',
            ],
            'from greater than to' => [
                'birthYearRangeString' => '2020-2014',
                'exception' => InvalidAgeRangeException::class,
                'exceptionMessage' => '"From" birth year should not be greater than the "to" birth year.',
            ],
        ];
    }
}
