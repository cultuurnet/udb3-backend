<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
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
    public function it_denormalizes_with_nested_childcare_start_and_end(): void
    {
        $subEvent = $this->denormalizer->denormalize(
            [
                'startDate' => '2021-05-17T16:00:00+00:00',
                'endDate' => '2021-05-17T22:00:00+00:00',
                'childcare' => ['start' => '15:00', 'end' => '23:00'],
            ],
            SubEvent::class
        );

        $this->assertSame('15:00', $subEvent->getChildcareTimeRange()->getStart()->getValue());
        $this->assertSame('23:00', $subEvent->getChildcareTimeRange()->getEnd()->getValue());
    }

    /**
     * @test
     */
    public function it_supports_denormalization_of_sub_event(): void
    {
        $this->assertTrue($this->denormalizer->supportsDenormalization([], SubEvent::class));
    }
}
