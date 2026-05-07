<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Audience;

use CultuurNet\UDB3\Model\ValueObject\Audience\BirthdateRange;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class BirthdateRangeNormalizerTest extends TestCase
{
    private BirthdateRangeNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new BirthdateRangeNormalizer();
    }

    /**
     * @test
     */
    public function it_normalizes_a_birthdate_range(): void
    {
        $result = $this->normalizer->normalize(
            new BirthdateRange(
                new DateTimeImmutable('2014-01-01'),
                new DateTimeImmutable('2020-12-31')
            )
        );

        $this->assertSame(
            ['from' => '2014-01-01', 'to' => '2020-12-31'],
            $result
        );
    }

    /**
     * @test
     */
    public function it_supports_normalization_of_birthdate_range(): void
    {
        $this->assertTrue(
            $this->normalizer->supportsNormalization(
                new BirthdateRange(
                    new DateTimeImmutable('2014-01-01'),
                    new DateTimeImmutable('2020-12-31')
                )
            )
        );
    }

    /**
     * @test
     */
    public function it_does_not_support_normalization_of_other_types(): void
    {
        $this->assertFalse(
            $this->normalizer->supportsNormalization(new \stdClass())
        );
    }
}
