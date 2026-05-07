<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Audience;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class BirthdateRangeTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_never_have_a_from_greater_than_the_to(): void
    {
        $this->expectException(InvalidAgeRangeException::class);
        $this->expectExceptionMessage('"From" birthdate should not be greater than the "to" birthdate.');

        new BirthdateRange(
            new DateTimeImmutable('2021-12-31'),
            new DateTimeImmutable('2021-01-01')
        );
    }

    /**
     * @test
     */
    public function it_should_return_the_given_from_and_to(): void
    {
        $range = new BirthdateRange(
            new DateTimeImmutable('2014-01-01'),
            new DateTimeImmutable('2020-12-31')
        );

        $this->assertEquals('2014-01-01', $range->getFrom()->format('Y-m-d'));
        $this->assertEquals('2020-12-31', $range->getTo()->format('Y-m-d'));
    }

    /**
     * @test
     */
    public function it_should_compare_two_ranges(): void
    {
        $range = new BirthdateRange(
            new DateTimeImmutable('2014-01-01'),
            new DateTimeImmutable('2020-12-31')
        );
        $sameRange = new BirthdateRange(
            new DateTimeImmutable('2014-01-01'),
            new DateTimeImmutable('2020-12-31')
        );
        $differentRange = new BirthdateRange(
            new DateTimeImmutable('2015-01-01'),
            new DateTimeImmutable('2021-12-31')
        );

        $this->assertTrue($range->sameAs($sameRange));
        $this->assertFalse($range->sameAs($differentRange));
    }
}
