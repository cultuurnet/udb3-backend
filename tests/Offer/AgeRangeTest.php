<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Model\ValueObject\Audience\Age;
use PHPUnit\Framework\TestCase;

class AgeRangeTest extends TestCase
{
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

    /**
     * @test
     * @dataProvider ageRangeStringProvider
     */
    public function it_should_return_a_string_representation_when_casted_to_string(
        string $ageRangeString,
        AgeRange $expectedRange,
        string $expectedAgeRangeString
    ): void {
        $actualAgeRangeString = (string) $expectedRange;

        $this->assertEquals($expectedAgeRangeString, $actualAgeRangeString);
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

    /**
     * @test
     */
    public function it_expects_from_age_to_not_exceed_to_age(): void
    {
        $this->expectException(InvalidAgeRangeException::class);
        new AgeRange(new Age(9), new Age(5));
    }

    /**
     * @test
     */
    public function it_should_provide_access_to_from_and_to_age(): void
    {
        $ageRange = new AgeRange(new Age(0), new Age(18));

        $this->assertEquals(new Age(0), $ageRange->getFrom());
        $this->assertEquals(new Age(18), $ageRange->getTo());
    }

    /**
     * @test
     */
    public function it_should_default_from_to_zero(): void
    {
        $ageRange = new AgeRange(null, new Age(18));

        $this->assertEquals(new Age(0), $ageRange->getFrom());
        $this->assertEquals(new Age(18), $ageRange->getTo());
    }

    /**
     * @test
     */
    public function it_can_compare_age_ranges(): void
    {
        $typicalAgeRange = new AgeRange(new Age(8), new Age(11));
        $sameAgeRange = new AgeRange(new Age(8), new Age(11));
        $otherAgeRange = new AgeRange(new Age(1), new Age(99));
        $allAges = new AgeRange();

        $this->assertTrue($typicalAgeRange->sameAs($sameAgeRange));
        $this->assertFalse($typicalAgeRange->sameAs($otherAgeRange));
        $this->assertFalse($typicalAgeRange->sameAs($allAges));
    }

    /**
     * @test
     */
    public function it_should_be_creatable_from_a_restricted_udb3_model_age_range(): void
    {
        $udb3ModelAgeRange = new \CultuurNet\UDB3\Model\ValueObject\Audience\AgeRange(
            new \CultuurNet\UDB3\Model\ValueObject\Audience\Age(8),
            new \CultuurNet\UDB3\Model\ValueObject\Audience\Age(12)
        );

        $expected = new AgeRange(new Age(8), new Age(12));
        $actual = AgeRange::fromUbd3ModelAgeRange($udb3ModelAgeRange);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_be_creatable_from_an_open_udb3_model_age_range(): void
    {
        $udb3ModelAgeRange = new \CultuurNet\UDB3\Model\ValueObject\Audience\AgeRange();

        $expected = new AgeRange(null, null);
        $actual = AgeRange::fromUbd3ModelAgeRange($udb3ModelAgeRange);

        $this->assertEquals($expected, $actual);
    }
}
