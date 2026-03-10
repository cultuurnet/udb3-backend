<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEventUpdate;
use PHPUnit\Framework\TestCase;

final class SubEventUpdateDenormalizerTest extends TestCase
{
    private SubEventUpdateDenormalizer $denormalizer;

    protected function setUp(): void
    {
        $this->denormalizer = new SubEventUpdateDenormalizer();
    }

    /**
     * @test
     */
    public function it_sets_childcare_time_range_to_null_when_both_fields_are_absent(): void
    {
        $update = $this->denormalizer->denormalize(['id' => 0], SubEventUpdate::class);

        $this->assertNull($update->getChildcareTimeRange());
    }

    /**
     * @test
     */
    public function it_denormalizes_with_both_childcare_times(): void
    {
        $update = $this->denormalizer->denormalize(
            ['id' => 0, 'childcareStartTime' => '15:00', 'childcareEndTime' => '23:00'],
            SubEventUpdate::class
        );

        $this->assertSame('15:00', $update->getChildcareTimeRange()->getStart());
        $this->assertSame('23:00', $update->getChildcareTimeRange()->getEnd());
    }

    /**
     * @test
     */
    public function it_denormalizes_with_only_childcare_start_time(): void
    {
        $update = $this->denormalizer->denormalize(
            ['id' => 0, 'childcareStartTime' => '15:00'],
            SubEventUpdate::class
        );

        $this->assertSame('15:00', $update->getChildcareTimeRange()->getStart());
        $this->assertNull($update->getChildcareTimeRange()->getEnd());
    }

    /**
     * @test
     */
    public function it_denormalizes_with_only_childcare_end_time(): void
    {
        $update = $this->denormalizer->denormalize(
            ['id' => 0, 'childcareEndTime' => '23:00'],
            SubEventUpdate::class
        );

        $this->assertNull($update->getChildcareTimeRange()->getStart());
        $this->assertSame('23:00', $update->getChildcareTimeRange()->getEnd());
    }

    /**
     * @test
     */
    public function it_supports_denormalization_of_sub_event_update(): void
    {
        $this->assertTrue($this->denormalizer->supportsDenormalization([], SubEventUpdate::class));
    }
}
