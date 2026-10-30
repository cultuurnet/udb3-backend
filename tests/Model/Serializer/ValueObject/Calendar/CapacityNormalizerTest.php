<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\Capacity;
use PHPUnit\Framework\TestCase;

final class CapacityNormalizerTest extends TestCase
{
    private CapacityNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new CapacityNormalizer();
    }

    /**
     * @test
     */
    public function it_normalizes_a_limited_capacity(): void
    {
        $this->assertSame(
            ['type' => 'Limited', 'maximum' => 30, 'taken' => 12],
            $this->normalizer->normalize(Capacity::limited(30, 12))
        );
    }

    /**
     * @test
     */
    public function it_omits_taken_when_not_set(): void
    {
        $this->assertSame(
            ['type' => 'Limited', 'maximum' => 30],
            $this->normalizer->normalize(Capacity::limited(30))
        );
    }

    /**
     * @test
     */
    public function it_normalizes_an_unlimited_capacity(): void
    {
        $this->assertSame(
            ['type' => 'Unlimited', 'taken' => 140],
            $this->normalizer->normalize(Capacity::unlimited(140))
        );
    }

    /**
     * @test
     */
    public function it_normalizes_an_unknown_capacity_to_an_empty_array(): void
    {
        $this->assertSame([], $this->normalizer->normalize(Capacity::unknown()));
    }

    /**
     * @test
     */
    public function it_supports_normalization_of_sub_event_capacity(): void
    {
        $this->assertTrue($this->normalizer->supportsNormalization(Capacity::unlimited()));
        $this->assertFalse($this->normalizer->supportsNormalization([]));
    }
}
