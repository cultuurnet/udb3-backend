<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\Capacity;
use PHPUnit\Framework\TestCase;

final class CapacityDenormalizerTest extends TestCase
{
    private CapacityDenormalizer $denormalizer;

    protected function setUp(): void
    {
        $this->denormalizer = new CapacityDenormalizer();
    }

    /**
     * @test
     */
    public function it_denormalizes_a_limited_capacity(): void
    {
        $this->assertEquals(
            Capacity::limited(30, 12),
            $this->denormalizer->denormalize(['type' => 'Limited', 'maximum' => 30, 'taken' => 12], Capacity::class)
        );
    }

    /**
     * @test
     */
    public function it_denormalizes_a_limited_capacity_without_taken(): void
    {
        $this->assertEquals(
            Capacity::limited(30),
            $this->denormalizer->denormalize(['type' => 'Limited', 'maximum' => 30], Capacity::class)
        );
    }

    /**
     * @test
     */
    public function it_denormalizes_an_unlimited_capacity(): void
    {
        $this->assertEquals(
            Capacity::unlimited(140),
            $this->denormalizer->denormalize(['type' => 'Unlimited', 'taken' => 140], Capacity::class)
        );
    }

    /**
     * @test
     */
    public function it_denormalizes_an_empty_object_to_an_unknown_capacity(): void
    {
        $this->assertTrue($this->denormalizer->denormalize([], Capacity::class)->isUnknown());
    }

    /**
     * @test
     */
    public function it_supports_denormalization_of_sub_event_capacity(): void
    {
        $this->assertTrue($this->denormalizer->supportsDenormalization([], Capacity::class));
    }
}
