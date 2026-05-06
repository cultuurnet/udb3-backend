<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Audience;

use CultuurNet\UDB3\Model\ValueObject\Audience\BirthdateRange;
use CultuurNet\UDB3\Model\ValueObject\Audience\InvalidAgeRangeException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\UnsupportedException;

final class BirthdateRangeDenormalizerTest extends TestCase
{
    private BirthdateRangeDenormalizer $denormalizer;

    protected function setUp(): void
    {
        $this->denormalizer = new BirthdateRangeDenormalizer();
    }

    /**
     * @test
     */
    public function it_denormalizes_a_birthdate_range(): void
    {
        $result = $this->denormalizer->denormalize(
            ['from' => '2014-01-01', 'to' => '2020-12-31'],
            BirthdateRange::class
        );

        $this->assertEquals(
            new BirthdateRange(
                new DateTimeImmutable('2014-01-01'),
                new DateTimeImmutable('2020-12-31')
            ),
            $result
        );
    }

    /**
     * @test
     */
    public function it_throws_when_from_is_greater_than_to(): void
    {
        $this->expectException(InvalidAgeRangeException::class);
        $this->expectExceptionMessage('"From" birthdate should not be greater than the "to" birthdate.');

        $this->denormalizer->denormalize(
            ['from' => '2020-12-31', 'to' => '2014-01-01'],
            BirthdateRange::class
        );
    }

    /**
     * @test
     */
    public function it_throws_when_class_is_not_supported(): void
    {
        $this->expectException(UnsupportedException::class);

        $this->denormalizer->denormalize(
            ['from' => '2014-01-01', 'to' => '2020-12-31'],
            \stdClass::class
        );
    }

    /**
     * @test
     */
    public function it_supports_denormalization_of_birthdate_range(): void
    {
        $this->assertTrue(
            $this->denormalizer->supportsDenormalization([], BirthdateRange::class)
        );
    }

    /**
     * @test
     */
    public function it_does_not_support_denormalization_of_other_types(): void
    {
        $this->assertFalse(
            $this->denormalizer->supportsDenormalization([], \stdClass::class)
        );
    }
}