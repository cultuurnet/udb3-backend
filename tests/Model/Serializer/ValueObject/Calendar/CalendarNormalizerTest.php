<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Day;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Days;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Hour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Minute;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SingleSubEventCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class CalendarNormalizerTest extends TestCase
{
    private CalendarNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new CalendarNormalizer();
    }

    /**
     * @test
     */
    public function it_preserves_capacity_and_remainingCapacity_on_permanent_calendar(): void
    {
        $openingHours = new OpeningHours(
            new OpeningHour(
                new Days(Day::monday()),
                new Time(new Hour(9), new Minute(0)),
                new Time(new Hour(17), new Minute(0))
            )
        );

        $calendar = (new PermanentCalendar($openingHours))
            ->withBookingAvailability(
                BookingAvailability::Available()
                    ->withCapacity(100)
                    ->withRemainingCapacity(42)
            );

        $normalized = $this->normalizer->normalize($calendar);

        $this->assertSame('Available', $normalized['bookingAvailability']['type']);
        $this->assertSame(100, $normalized['bookingAvailability']['capacity']);
        $this->assertSame(42, $normalized['bookingAvailability']['remainingCapacity']);
    }

    /**
     * @test
     */
    public function it_preserves_capacity_on_calendar_with_available_subevent(): void
    {
        $timezone = new DateTimeZone('Europe/Brussels');
        $startDate = new DateTimeImmutable('2025-03-01 10:00:00', $timezone);
        $endDate = new DateTimeImmutable('2025-03-01 20:00:00', $timezone);

        $subEvent = SubEvent::createAvailable(new DateRange($startDate, $endDate));

        $calendar = (new SingleSubEventCalendar($subEvent))
            ->withBookingAvailability(
                BookingAvailability::Available()
                    ->withCapacity(200)
            );

        $normalized = $this->normalizer->normalize($calendar);

        $this->assertSame('Available', $normalized['bookingAvailability']['type']);
        $this->assertSame(200, $normalized['bookingAvailability']['capacity']);
    }

    /**
     * @test
     */
    public function it_derives_unavailable_type_from_unavailable_subevent_while_preserving_capacity(): void
    {
        $timezone = new DateTimeZone('Europe/Brussels');
        $startDate = new DateTimeImmutable('2025-03-01 10:00:00', $timezone);
        $endDate = new DateTimeImmutable('2025-03-01 20:00:00', $timezone);

        $subEvent = SubEvent::createAvailable(new DateRange($startDate, $endDate))
            ->withStatus(new Status(StatusType::Unavailable()))
            ->withBookingAvailability(BookingAvailability::Unavailable());

        $calendar = (new SingleSubEventCalendar($subEvent))
            ->withBookingAvailability(
                BookingAvailability::Available()
                    ->withCapacity(300)
            );

        $normalized = $this->normalizer->normalize($calendar);

        // Type should be derived as Unavailable from the sub-event
        $this->assertSame('Unavailable', $normalized['bookingAvailability']['type']);
        // But capacity should be preserved from the stored top-level value
        $this->assertSame(300, $normalized['bookingAvailability']['capacity']);
    }
}
