<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\TimeImmutableRange;
use PHPUnit\Framework\TestCase;

final class SubEventDenormalizerTest extends TestCase
{
    private SubEventDenormalizer $denormalizer;

    protected function setUp(): void
    {
        $this->denormalizer = new SubEventDenormalizer();
    }

    /**
     * @test
     */
    public function it_denormalizes_without_childcare_times(): void
    {
        $subEvent = $this->denormalizer->denormalize(
            [
                'startDate' => '2021-05-17T16:00:00+00:00',
                'endDate' => '2021-05-17T22:00:00+00:00',
            ],
            SubEvent::class
        );

        $this->assertNull($subEvent->getChildcareTimeRange());
    }

    /**
     * @test
     */
    public function it_denormalizes_with_both_childcare_times(): void
    {
        $subEvent = $this->denormalizer->denormalize(
            [
                'startDate' => '2021-05-17T16:00:00+00:00',
                'endDate' => '2021-05-17T22:00:00+00:00',
                'childcareStartTime' => '15:00',
                'childcareEndTime' => '23:00',
            ],
            SubEvent::class
        );

        $this->assertSame('15:00', $subEvent->getChildcareTimeRange()->getStart());
        $this->assertSame('23:00', $subEvent->getChildcareTimeRange()->getEnd());
    }

    /**
     * @test
     */
    public function it_denormalizes_with_only_childcare_start_time(): void
    {
        $subEvent = $this->denormalizer->denormalize(
            [
                'startDate' => '2021-05-17T16:00:00+00:00',
                'endDate' => '2021-05-17T22:00:00+00:00',
                'childcareStartTime' => '15:00',
            ],
            SubEvent::class
        );

        $this->assertSame('15:00', $subEvent->getChildcareTimeRange()->getStart());
        $this->assertNull($subEvent->getChildcareTimeRange()->getEnd());
    }

    /**
     * @test
     */
    public function it_denormalizes_with_only_childcare_end_time(): void
    {
        $subEvent = $this->denormalizer->denormalize(
            [
                'startDate' => '2021-05-17T16:00:00+00:00',
                'endDate' => '2021-05-17T22:00:00+00:00',
                'childcareEndTime' => '23:00',
            ],
            SubEvent::class
        );

        $this->assertNull($subEvent->getChildcareTimeRange()->getStart());
        $this->assertSame('23:00', $subEvent->getChildcareTimeRange()->getEnd());
    }

    /**
     * @test
     */
    public function it_supports_denormalization_of_sub_event(): void
    {
        $this->assertTrue($this->denormalizer->supportsDenormalization([], SubEvent::class));
    }
}
