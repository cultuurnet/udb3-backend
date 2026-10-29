<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEventUpdate;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEventBookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
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

        $this->assertNull($update->getChildcareTimeRange());
    }

    /**
     * @test
     */
    public function it_does_not_set_childcare_time_range_when_childcare_is_null(): void
    {
        // null is falsy for isset(), so it is treated the same as an absent key (preserve).
        // The JSON schema is expected to reject null at the HTTP layer.
        $update = $this->denormalizer->denormalize(['id' => 0, 'childcare' => null], SubEventUpdate::class);

        $this->assertNull($update->getChildcareTimeRange());
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

        $this->assertNotNull($update->getChildcareTimeRange());
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

        $this->assertNotNull($update->getChildcareTimeRange());
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

        $this->assertNotNull($update->getChildcareTimeRange());
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

        $this->assertNotNull($update->getChildcareTimeRange());
        $this->assertNull($update->getChildcareTimeRange()->getStart());
        $this->assertNull($update->getChildcareTimeRange()->getEnd());
    }

    /**
     * @test
     */
    public function it_does_not_set_overnight_stay_when_key_is_absent(): void
    {
        $update = $this->denormalizer->denormalize(['id' => 0], SubEventUpdate::class);

        $this->assertNull($update->getHasOvernightStay());
    }

    /**
     * @test
     */
    public function it_sets_overnight_stay_to_true(): void
    {
        $update = $this->denormalizer->denormalize(['id' => 0, 'hasOvernightStay' => true], SubEventUpdate::class);

        $this->assertTrue($update->getHasOvernightStay());
    }

    /**
     * @test
     */
    public function it_sets_overnight_stay_to_false(): void
    {
        $update = $this->denormalizer->denormalize(['id' => 0, 'hasOvernightStay' => false], SubEventUpdate::class);

        $this->assertFalse($update->getHasOvernightStay());
    }

    /**
     * @test
     */
    public function it_does_not_update_the_waiting_list_url_without_booking_availability(): void
    {
        $update = $this->denormalizer->denormalize(['id' => 0], SubEventUpdate::class);

        $this->assertEquals($this->getBookingAvailability(), $update->applyToBookingAvailability($this->getBookingAvailability()));
    }

    /**
     * @test
     */
    public function it_does_not_update_the_waiting_list_url_when_it_is_absent_from_booking_availability(): void
    {
        $update = $this->denormalizer->denormalize(
            ['id' => 0, 'bookingAvailability' => ['type' => 'Unavailable']],
            SubEventUpdate::class
        );

        $this->assertEquals(
            BookingAvailability::Unavailable()->withWaitingListUrl(new Url('https://publiq.be/wachtlijst')),
            $update->applyToBookingAvailability($this->getBookingAvailability())
        );
    }

    /**
     * @test
     */
    public function it_sets_the_waiting_list_url(): void
    {
        $update = $this->denormalizer->denormalize(
            ['id' => 0, 'bookingAvailability' => ['type' => 'Unavailable', 'waitingListUrl' => 'https://publiq.be/nieuwe-wachtlijst']],
            SubEventUpdate::class
        );

        $this->assertEquals(
            BookingAvailability::Unavailable()->withWaitingListUrl(new Url('https://publiq.be/nieuwe-wachtlijst')),
            $update->applyToBookingAvailability($this->getBookingAvailability())
        );
    }

    /**
     * @test
     */
    public function it_clears_the_waiting_list_url_when_it_is_an_empty_string(): void
    {
        $update = $this->denormalizer->denormalize(
            ['id' => 0, 'bookingAvailability' => ['type' => 'Available', 'waitingListUrl' => '']],
            SubEventUpdate::class
        );

        $this->assertEquals(
            BookingAvailability::Available(),
            $update->applyToBookingAvailability($this->getBookingAvailability())
        );
    }

    /**
     * @test
     */
    public function it_does_not_update_the_waiting_list_url_when_it_is_null(): void
    {
        $update = $this->denormalizer->denormalize(
            ['id' => 0, 'bookingAvailability' => ['type' => 'Available', 'waitingListUrl' => null]],
            SubEventUpdate::class
        );

        $this->assertEquals($this->getBookingAvailability(), $update->applyToBookingAvailability($this->getBookingAvailability()));
    }

    /**
     * @test
     */
    public function it_supports_denormalization_of_sub_event_update(): void
    {
        $this->assertTrue($this->denormalizer->supportsDenormalization([], SubEventUpdate::class));
    }

    /**
     * @test
     */
    public function it_does_not_set_booking_info_when_absent(): void
    {
        $update = $this->denormalizer->denormalize(['id' => 0], SubEventUpdate::class);

        $this->assertNull($update->getBookingInfo());
    }

    /**
     * @test
     */
    public function it_sets_the_booking_info(): void
    {
        $update = $this->denormalizer->denormalize(
            ['id' => 0, 'bookingInfo' => ['url' => 'https://www.example.com/reserve']],
            SubEventUpdate::class
        );

        $this->assertEquals(new SubEventBookingInfo(new Url('https://www.example.com/reserve')), $update->getBookingInfo());
    }

    private function getBookingAvailability(): BookingAvailability
    {
        return BookingAvailability::Available()->withWaitingListUrl(new Url('https://publiq.be/wachtlijst'));
    }
}
