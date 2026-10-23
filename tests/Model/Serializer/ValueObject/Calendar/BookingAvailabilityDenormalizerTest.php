<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

final class BookingAvailabilityDenormalizerTest extends TestCase
{
    private BookingAvailabilityDenormalizer $denormalizer;

    protected function setUp(): void
    {
        $this->denormalizer = new BookingAvailabilityDenormalizer();
    }

    /**
     * @test
     */
    public function it_denormalizes_with_explicit_type(): void
    {
        $result = $this->denormalizer->denormalize(
            ['type' => 'Available'],
            BookingAvailability::class
        );

        $this->assertEquals(BookingAvailabilityType::Available(), $result->getType());
    }

    /**
     * @test
     */
    public function it_denormalizes_with_explicit_unavailable_type(): void
    {
        $result = $this->denormalizer->denormalize(
            ['type' => 'Unavailable'],
            BookingAvailability::class
        );

        $this->assertEquals(BookingAvailabilityType::Unavailable(), $result->getType());
    }

    /**
     * @test
     */
    public function it_denormalizes_without_a_waiting_list_url(): void
    {
        $result = $this->denormalizer->denormalize(
            ['type' => 'Unavailable'],
            BookingAvailability::class
        );

        $this->assertNull($result->getWaitingListUrl());
    }

    /**
     * @test
     */
    public function it_denormalizes_with_a_waiting_list_url(): void
    {
        $result = $this->denormalizer->denormalize(
            ['type' => 'Unavailable', 'waitingListUrl' => 'https://publiq.be/wachtlijst'],
            BookingAvailability::class
        );

        $this->assertEquals(new Url('https://publiq.be/wachtlijst'), $result->getWaitingListUrl());
    }

    /**
     * @test
     */
    public function it_denormalizes_a_null_waiting_list_url_as_no_waiting_list_url(): void
    {
        $result = $this->denormalizer->denormalize(
            ['type' => 'Unavailable', 'waitingListUrl' => null],
            BookingAvailability::class
        );

        $this->assertNull($result->getWaitingListUrl());
    }

    /**
     * @test
     */
    public function it_denormalizes_an_empty_waiting_list_url_as_no_waiting_list_url(): void
    {
        $result = $this->denormalizer->denormalize(
            ['type' => 'Unavailable', 'waitingListUrl' => ''],
            BookingAvailability::class
        );

        $this->assertNull($result->getWaitingListUrl());
    }

    /**
     * The capacity and remainingCapacity properties were removed, so any value that is still sent
     * has to be ignored instead of validated.
     *
     * @see https://jira.publiq.be/browse/III-7392
     *
     * @test
     */
    public function it_ignores_capacity_and_remaining_capacity(): void
    {
        $result = $this->denormalizer->denormalize(
            ['type' => 'Available', 'capacity' => 10, 'remainingCapacity' => 0],
            BookingAvailability::class
        );

        $this->assertEquals(BookingAvailabilityType::Available(), $result->getType());
    }

    /**
     * @test
     */
    public function it_supports_denormalization_of_booking_availability(): void
    {
        $this->assertTrue(
            $this->denormalizer->supportsDenormalization([], BookingAvailability::class)
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
