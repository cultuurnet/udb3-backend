<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Calendar;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Event\ValueObjects\StatusReason;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Offer\ValueObjects\BookingAvailability;
use CultuurNet\UDB3\Offer\ValueObjects\BookingAvailabilityType;
use PHPUnit\Framework\TestCase;

class TimestampTest extends TestCase
{
    public const START_DATE_KEY = 'startDate';
    public const END_DATE_KEY = 'endDate';

    public const START_DATE = '2016-01-03T01:01:01+01:00';
    public const END_DATE = '2016-01-07T01:01:01+01:00';

    private Timestamp $timestamp;

    public function setUp(): void
    {
        $this->timestamp = new Timestamp(
            DateTimeFactory::fromAtom(self::START_DATE),
            DateTimeFactory::fromAtom(self::END_DATE)
        );
    }

    /**
     * @test
     */
    public function it_stores_a_start_and_end_date(): void
    {
        $this->assertEquals(
            DateTimeFactory::fromAtom(self::START_DATE),
            $this->timestamp->getStartDate()
        );

        $this->assertEquals(
            DateTimeFactory::fromAtom(self::END_DATE),
            $this->timestamp->getEndDate()
        );
    }

    /**
     * @test
     */
    public function its_end_date_can_not_be_earlier_than_start_date(): void
    {
        $pastDate = '2016-01-03T00:01:01+01:00';

        $this->expectException(EndDateCanNotBeEarlierThanStartDate::class);
        $this->expectExceptionMessage('End date can not be earlier than start date.');

        new Timestamp(
            DateTimeFactory::fromAtom(self::START_DATE),
            DateTimeFactory::fromAtom($pastDate)
        );
    }

    /**
     * @test
     */
    public function it_will_add_the_default_event_status(): void
    {
        $timestamp = new Timestamp(
            DateTimeFactory::fromAtom(self::START_DATE),
            DateTimeFactory::fromAtom(self::END_DATE)
        );

        $this->assertEquals(
            new Status(StatusType::Available(), []),
            $timestamp->getStatus()
        );
    }

    /**
     * @test
     */
    public function it_has_a_default_booking_availability(): void
    {
        $timestamp = new Timestamp(
            DateTimeFactory::fromAtom(self::START_DATE),
            DateTimeFactory::fromAtom(self::END_DATE)
        );

        $this->assertEquals(BookingAvailability::available(), $timestamp->getBookingAvailability());
    }

    /**
     * @test
     */
    public function it_can_serialize_and_deserialize(): void
    {
        $timestamp = new Timestamp(
            DateTimeFactory::fromAtom(self::START_DATE),
            DateTimeFactory::fromAtom(self::END_DATE),
            new Status(
                StatusType::Unavailable(),
                [
                    new StatusReason(new Language('nl'), 'Vanavond niet, schat'),
                ]
            ),
            BookingAvailability::unavailable()
        );

        $serialized = [
            'startDate' => self::START_DATE,
            'endDate' => self::END_DATE,
            'status' => [
                'type' => StatusType::Unavailable()->toString(),
                'reason' => [
                    'nl' => 'Vanavond niet, schat',
                ],
            ],
            'bookingAvailability' => [
                'type' => BookingAvailabilityType::unavailable()->toString(),
            ],
        ];

        $this->assertEquals($serialized, $timestamp->serialize());
        $this->assertEquals($timestamp, Timestamp::deserialize($serialized));
    }

    /**
     * @test
     */
    public function itCanChangeStatus(): void
    {
        $timestamp = new Timestamp(
            DateTimeFactory::fromAtom(self::START_DATE),
            DateTimeFactory::fromAtom(self::END_DATE),
            new Status(StatusType::Available(), [])
        );

        $newStatus = new Status(
            StatusType::Unavailable(),
            [
                new StatusReason(new Language('nl'), 'Het mag niet van de afgevaardigde van de eerste minister'),
            ]
        );

        $expected = new Timestamp(
            DateTimeFactory::fromAtom(self::START_DATE),
            DateTimeFactory::fromAtom(self::END_DATE),
            $newStatus
        );

        $this->assertEquals(
            $expected,
            $timestamp->withStatus($newStatus)
        );
    }

    /**
     * @test
     */
    public function it_allows_changing_the_booking_availability(): void
    {
        $timestamp = new Timestamp(
            DateTimeFactory::fromAtom(self::START_DATE),
            DateTimeFactory::fromAtom(self::END_DATE)
        );

        $updatedTimestamp = $timestamp->withBookingAvailability(BookingAvailability::unavailable());

        $this->assertEquals(
            new Timestamp(
                DateTimeFactory::fromAtom(self::START_DATE),
                DateTimeFactory::fromAtom(self::END_DATE),
                new Status(StatusType::Available(), []),
                BookingAvailability::unavailable()
            ),
            $updatedTimestamp
        );
    }

    /**
     * @test
     */
    public function it_will_set_end_date_to_start_date_when_deserializing_incorrect_events(): void
    {
        $expected = new Timestamp(
            DateTimeFactory::fromAtom(self::END_DATE),
            DateTimeFactory::fromAtom(self::END_DATE),
            new Status(StatusType::Available(), [])
        );

        $serialized = [
            'startDate' => self::END_DATE,
            'endDate' => self::START_DATE,
            'status' => [
                'type' => 'Available',
            ],
        ];

        $this->assertEquals($expected, Timestamp::deserialize($serialized));
    }
}
