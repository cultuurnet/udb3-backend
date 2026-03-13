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
    public function it_does_not_set_childcare_time_range_when_childcare_is_absent(): void
    {
        $update = $this->denormalizer->denormalize(['id' => 0], SubEventUpdate::class);

        $this->assertFalse($update->isChildcareTimeRangeSet());
    }

    /**
     * @test
     */
    public function it_does_not_set_childcare_time_range_when_childcare_is_null(): void
    {
        // null is falsy for isset(), so it is treated the same as an absent key (preserve).
        // The JSON schema is expected to reject null at the HTTP layer.
        $update = $this->denormalizer->denormalize(['id' => 0, 'childcare' => null], SubEventUpdate::class);

        $this->assertFalse($update->isChildcareTimeRangeSet());
    }

    /**
     * @test
     */
    public function it_denormalizes_with_childcare_start_and_end(): void
    {
        $update = $this->denormalizer->denormalize(
            ['id' => 0, 'childcare' => ['start' => '15:00', 'end' => '23:00']],
            SubEventUpdate::class
        );

        $this->assertTrue($update->isChildcareTimeRangeSet());
        $this->assertSame('15:00', $update->getChildcareTimeRange()->getStart()->getValue());
        $this->assertSame('23:00', $update->getChildcareTimeRange()->getEnd()->getValue());
    }

    /**
     * @test
     */
    public function it_denormalizes_with_only_childcare_start(): void
    {
        $update = $this->denormalizer->denormalize(
            ['id' => 0, 'childcare' => ['start' => '15:00']],
            SubEventUpdate::class
        );

        $this->assertTrue($update->isChildcareTimeRangeSet());
        $this->assertSame('15:00', $update->getChildcareTimeRange()->getStart()->getValue());
        $this->assertNull($update->getChildcareTimeRange()->getEnd());
    }

    /**
     * @test
     */
    public function it_denormalizes_with_only_childcare_end(): void
    {
        $update = $this->denormalizer->denormalize(
            ['id' => 0, 'childcare' => ['end' => '23:00']],
            SubEventUpdate::class
        );

        $this->assertTrue($update->isChildcareTimeRangeSet());
        $this->assertNull($update->getChildcareTimeRange()->getStart());
        $this->assertSame('23:00', $update->getChildcareTimeRange()->getEnd()->getValue());
    }

    /**
     * @test
     */
    public function it_clears_childcare_time_range_when_childcare_is_empty_object(): void
    {
        $update = $this->denormalizer->denormalize(
            ['id' => 0, 'childcare' => []],
            SubEventUpdate::class
        );

        $this->assertTrue($update->isChildcareTimeRangeSet());
        $this->assertNull($update->getChildcareTimeRange());
    }

    /**
     * @test
     */
    public function it_supports_denormalization_of_sub_event_update(): void
    {
        $this->assertTrue($this->denormalizer->supportsDenormalization([], SubEventUpdate::class));
    }
}
