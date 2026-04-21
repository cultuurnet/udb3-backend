<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\AdjustedDescription;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\ClosedDay;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\ClosedDays;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Day;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Days;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Hour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Minute;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PeriodicCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SingleSubEventCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\AdjustedDay;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\AdjustedDays;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedAdjustedDescription;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
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

    /**
     * @test
     */
    public function it_emits_opening_hours_closed_days_for_periodic_calendar(): void
    {
        $timezone = new DateTimeZone('Europe/Brussels');
        $startDate = new DateTimeImmutable('2024-01-01 00:00:00', $timezone);
        $endDate = new DateTimeImmutable('2024-12-31 23:59:59', $timezone);

        $openingHours = new OpeningHours(
            new OpeningHour(
                new Days(Day::monday()),
                new Time(new Hour(9), new Minute(0)),
                new Time(new Hour(17), new Minute(0))
            )
        );

        $closedDays = new ClosedDays(
            new ClosedDay(
                new DateTimeImmutable('2024-12-25T00:00:00+00:00'),
                new DateTimeImmutable('2024-12-25T23:59:59+00:00')
            )
        );

        $calendar = (new PeriodicCalendar(new DateRange($startDate, $endDate), $openingHours))
            ->withClosedDays($closedDays);

        $normalized = $this->normalizer->normalize($calendar);

        $this->assertArrayHasKey('openingHoursClosedDays', $normalized);
        $this->assertIsArray($normalized['openingHoursClosedDays']);
        $this->assertCount(1, $normalized['openingHoursClosedDays']);
        $this->assertSame('2024-12-25', $normalized['openingHoursClosedDays'][0]['startDate']);
        $this->assertSame('2024-12-25', $normalized['openingHoursClosedDays'][0]['endDate']);
    }

    /**
     * @test
     */
    public function it_does_not_emit_opening_hours_closed_days_when_empty(): void
    {
        $timezone = new DateTimeZone('Europe/Brussels');
        $startDate = new DateTimeImmutable('2024-01-01 00:00:00', $timezone);
        $endDate = new DateTimeImmutable('2024-12-31 23:59:59', $timezone);

        $openingHours = new OpeningHours(
            new OpeningHour(
                new Days(Day::monday()),
                new Time(new Hour(9), new Minute(0)),
                new Time(new Hour(17), new Minute(0))
            )
        );

        $calendar = new PeriodicCalendar(new DateRange($startDate, $endDate), $openingHours);

        $normalized = $this->normalizer->normalize($calendar);

        $this->assertArrayNotHasKey('openingHoursClosedDays', $normalized);
    }

    /**
     * @test
     */
    public function it_emits_opening_hours_closed_days_with_descriptions(): void
    {
        $openingHours = new OpeningHours(
            new OpeningHour(
                new Days(Day::monday()),
                new Time(new Hour(9), new Minute(0)),
                new Time(new Hour(17), new Minute(0))
            )
        );

        $description = new TranslatedAdjustedDescription(
            new Language('nl'),
            new AdjustedDescription('Kerstfeest')
        );
        $description = $description->withTranslation(
            new Language('fr'),
            new AdjustedDescription('Noël')
        );

        $closedDays = new ClosedDays(
            new ClosedDay(
                new DateTimeImmutable('2024-12-25T00:00:00+00:00'),
                new DateTimeImmutable('2024-12-25T23:59:59+00:00'),
                $description
            )
        );

        $calendar = (new PermanentCalendar($openingHours))
            ->withClosedDays($closedDays);

        $normalized = $this->normalizer->normalize($calendar);

        $this->assertArrayHasKey('openingHoursClosedDays', $normalized);
        $this->assertIsArray($normalized['openingHoursClosedDays'][0]['description']);
        $this->assertSame('Kerstfeest', $normalized['openingHoursClosedDays'][0]['description']['nl']);
        $this->assertSame('Noël', $normalized['openingHoursClosedDays'][0]['description']['fr']);
    }

    /**
     * @test
     */
    public function it_emits_opening_hours_adjusted_for_permanent_calendar(): void
    {
        $openingHours = new OpeningHours(
            new OpeningHour(
                new Days(Day::monday()),
                new Time(new Hour(9), new Minute(0)),
                new Time(new Hour(17), new Minute(0))
            )
        );

        $adjustedDays = new AdjustedDays(
            new AdjustedDay(
                new DateTimeImmutable('2026-12-25T00:00:00+00:00'),
                new DateTimeImmutable('2026-12-31T00:00:00+00:00'),
                $openingHours
            )
        );

        $calendar = (new PermanentCalendar($openingHours))
            ->withAdjustedDays($adjustedDays);

        $normalized = $this->normalizer->normalize($calendar);

        $this->assertArrayHasKey('openingHoursAdjustedDays', $normalized);
        $this->assertIsArray($normalized['openingHoursAdjustedDays']);
        $this->assertCount(1, $normalized['openingHoursAdjustedDays']);
        $this->assertSame('2026-12-25', $normalized['openingHoursAdjustedDays'][0]['startDate']);
        $this->assertSame('2026-12-31', $normalized['openingHoursAdjustedDays'][0]['endDate']);
        $this->assertIsArray($normalized['openingHoursAdjustedDays'][0]['openingHours']);
    }

    /**
     * @test
     */
    public function it_emits_opening_hours_adjusted_with_description(): void
    {
        $openingHours = new OpeningHours(
            new OpeningHour(
                new Days(Day::monday()),
                new Time(new Hour(9), new Minute(0)),
                new Time(new Hour(17), new Minute(0))
            )
        );

        $description = (new TranslatedAdjustedDescription(
            new Language('nl'),
            new AdjustedDescription('Kerstvakantie')
        ))->withTranslation(new Language('fr'), new AdjustedDescription('Vacances de Noël'));

        $adjustedDays = new AdjustedDays(
            new AdjustedDay(
                new DateTimeImmutable('2026-12-25T00:00:00+00:00'),
                new DateTimeImmutable('2026-12-31T00:00:00+00:00'),
                $openingHours,
                $description
            )
        );

        $calendar = (new PermanentCalendar($openingHours))
            ->withAdjustedDays($adjustedDays);

        $normalized = $this->normalizer->normalize($calendar);

        $this->assertArrayHasKey('openingHoursAdjustedDays', $normalized);
        $this->assertIsArray($normalized['openingHoursAdjustedDays'][0]['description']);
        $this->assertSame('Kerstvakantie', $normalized['openingHoursAdjustedDays'][0]['description']['nl']);
        $this->assertSame('Vacances de Noël', $normalized['openingHoursAdjustedDays'][0]['description']['fr']);
    }

    /**
     * @test
     */
    public function it_does_not_emit_opening_hours_adjusted_when_empty(): void
    {
        $calendar = new PermanentCalendar(new OpeningHours(
            new OpeningHour(
                new Days(Day::monday()),
                new Time(new Hour(9), new Minute(0)),
                new Time(new Hour(17), new Minute(0))
            )
        ));

        $normalized = $this->normalizer->normalize($calendar);

        $this->assertArrayNotHasKey('openingHoursAdjustedDays', $normalized);
    }

    /**
     * @test
     */
    public function it_emits_closed_days_sorted_by_start_date(): void
    {
        $openingHours = new OpeningHours(
            new OpeningHour(
                new Days(Day::monday()),
                new Time(new Hour(9), new Minute(0)),
                new Time(new Hour(17), new Minute(0))
            )
        );

        $closedDays = new ClosedDays(
            new ClosedDay(
                new DateTimeImmutable('2024-12-25T00:00:00+00:00'),
                new DateTimeImmutable('2024-12-25T23:59:59+00:00')
            ),
            new ClosedDay(
                new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
                new DateTimeImmutable('2024-01-01T23:59:59+00:00')
            ),
            new ClosedDay(
                new DateTimeImmutable('2024-07-21T00:00:00+00:00'),
                new DateTimeImmutable('2024-07-21T23:59:59+00:00')
            )
        );

        $calendar = (new PermanentCalendar($openingHours))
            ->withClosedDays($closedDays);

        $normalized = $this->normalizer->normalize($calendar);

        $this->assertCount(3, $normalized['openingHoursClosedDays']);
        // Should be sorted by startDate
        $this->assertSame('2024-01-01', $normalized['openingHoursClosedDays'][0]['startDate']);
        $this->assertSame('2024-07-21', $normalized['openingHoursClosedDays'][1]['startDate']);
        $this->assertSame('2024-12-25', $normalized['openingHoursClosedDays'][2]['startDate']);
    }
}
