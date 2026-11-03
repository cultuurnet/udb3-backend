<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEventBookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
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
    public function it_leaves_overnight_stay_null_when_absent(): void
    {
        $subEvent = $this->denormalizer->denormalize(
            [
                'startDate' => '2026-07-01T09:00:00+02:00',
                'endDate' => '2026-07-05T17:00:00+02:00',
            ],
            SubEvent::class
        );

        $this->assertNull($subEvent->getHasOvernightStay());
    }

    /**
     * @test
     */
    public function it_denormalizes_overnight_stay_true(): void
    {
        $subEvent = $this->denormalizer->denormalize(
            [
                'startDate' => '2026-07-01T09:00:00+02:00',
                'endDate' => '2026-07-05T17:00:00+02:00',
                'hasOvernightStay' => true,
            ],
            SubEvent::class
        );

        $this->assertTrue($subEvent->getHasOvernightStay());
    }

    /**
     * @test
     */
    public function it_denormalizes_overnight_stay_false(): void
    {
        $subEvent = $this->denormalizer->denormalize(
            [
                'startDate' => '2026-07-01T09:00:00+02:00',
                'endDate' => '2026-07-05T17:00:00+02:00',
                'hasOvernightStay' => false,
            ],
            SubEvent::class
        );

        $this->assertFalse($subEvent->getHasOvernightStay());
    }

    /**
     * @test
     */
    public function it_supports_denormalization_of_sub_event(): void
    {
        $this->assertTrue($this->denormalizer->supportsDenormalization([], SubEvent::class));
    }

    /**
     * @test
     */
    public function it_denormalizes_the_booking_info(): void
    {
        $subEvent = $this->denormalizer->denormalize(
            [
                'startDate' => '2026-07-01T09:00:00+02:00',
                'endDate' => '2026-07-05T17:00:00+02:00',
                'bookingInfo' => ['url' => 'https://www.example.com/reserve'],
            ],
            SubEvent::class
        );

        $this->assertEquals(new SubEventBookingInfo(new Url('https://www.example.com/reserve')), $subEvent->getBookingInfo());
    }

    /**
     * @test
     */
    public function it_ignores_a_stored_booking_info_without_url(): void
    {
        $subEvent = $this->denormalizer->denormalize(
            [
                'startDate' => '2026-05-01T09:00:00+02:00',
                'endDate' => '2026-05-05T17:00:00+02:00',
                'bookingInfo' => [
                    'phone' => '0123456789',
                ],
            ],
            SubEvent::class
        );

        $this->assertNull($subEvent->getBookingInfo());
    }
}
