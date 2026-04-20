<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\OpeningHours\AdjustedDaysDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\OpeningHours\AdjustedDayNormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\OpeningHours\ClosedDayNormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\OpeningHours\ClosedDaysDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarWithClosedDays;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarWithDateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarWithOpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarWithAdjustedDays;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarWithSubEvents;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\MultipleSubEventsCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\AdjustedDay;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\AdjustedDays;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\ClosedDay;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\ClosedDays;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PeriodicCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SingleSubEventCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvents;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

// This class needs to be used to:
// - serialze a calendar to the event store
// - deserialize a calendar from the event store
final class CalendarSerializer implements Serializable
{
    private Calendar $calendar;

    public function __construct(Calendar $calendar)
    {
        $this->calendar = $calendar;
    }

    public static function deserialize(array $data): Calendar
    {
        $calendarType = new CalendarType($data['type']);

        $startDate = !empty($data['startDate']) ? self::deserializeDateTime($data['startDate']) : null;
        $endDate = !empty($data['endDate']) ? self::deserializeDateTime($data['endDate']) : null;

        // Fix for old events that could have a end date before the start date
        if ($startDate && $endDate && ($startDate > $endDate)) {
            $endDate = $startDate;
        }

        // Backwards compatibility for serialized single or multiple calendar types that are missing sub events but do
        // have a start and end date.
        $subEvents = [];
        if ($calendarType->sameAs(CalendarType::single()) || $calendarType->sameAs(CalendarType::multiple())) {
            if ($startDate) {
                $subEvents[] = new SubEvent(
                    new DateRange(
                        $startDate,
                        $endDate ?: $startDate
                    ),
                    new Status(StatusType::Available()),
                    new BookingAvailability(BookingAvailabilityType::Available()),
                    new BookingInfo(),
                );
            }
        }

        if (!empty($data['timestamps'])) {
            $subEvents = array_map(
                function ($subEvent) {
                    return (new SubEventDenormalizer())->denormalize($subEvent, SubEvent::class);
                },
                $data['timestamps']
            );
        }

        $openingHours = [];
        if (!empty($data['openingHours'])) {
            $openingHours = array_map(
                function ($openingHourData) {
                    return (new OpeningHourDenormalizer())->denormalize($openingHourData, OpeningHour::class);
                },
                $data['openingHours']
            );
        }

        // There are cases where the calendar type does not match the number of sub events.
        // For those cases the amount of sub events dictates the calendar type.
        if ($calendarType->sameAs(CalendarType::single()) && count($subEvents) > 1) {
            $calendarType = CalendarType::multiple();
        }
        if ($calendarType->sameAs(CalendarType::multiple()) && count($subEvents) === 1) {
            $calendarType = CalendarType::single();
        }

        switch ($calendarType) {
            case CalendarType::single():
                $calendar = new SingleSubEventCalendar($subEvents[0]);
                break;
            case CalendarType::multiple():
                $calendar = new MultipleSubEventsCalendar(new SubEvents(...$subEvents));
                break;
            case CalendarType::periodic():
                $calendar = new PeriodicCalendar(new DateRange($startDate, $endDate), new OpeningHours(...$openingHours));
                if (isset($data['openingHoursClosedDays'])) {
                    $calendar = $calendar->withClosedDays(
                        (new ClosedDaysDenormalizer())->denormalize($data['openingHoursClosedDays'], ClosedDays::class)
                    );
                }
                if (isset($data['openingHoursAdjustedDays'])) {
                    $calendar = $calendar->withAdjustedDays(
                        (new AdjustedDaysDenormalizer())->denormalize($data['openingHoursAdjustedDays'], AdjustedDays::class)
                    );
                }
                break;
            case CalendarType::permanent():
                $calendar = new PermanentCalendar(new OpeningHours(...$openingHours));
                if (isset($data['openingHoursClosedDays'])) {
                    $calendar = $calendar->withClosedDays(
                        (new ClosedDaysDenormalizer())->denormalize($data['openingHoursClosedDays'], ClosedDays::class)
                    );
                }
                if (isset($data['openingHoursAdjustedDays'])) {
                    $calendar = $calendar->withAdjustedDays(
                        (new AdjustedDaysDenormalizer())->denormalize($data['openingHoursAdjustedDays'], AdjustedDays::class)
                    );
                }
                break;
            default:
                throw new InvalidArgumentException('Invalid calendar type provided!');
        }

        if (!empty($data['status'])) {
            $status = (new StatusDenormalizer())->denormalize($data['status'], Status::class);
            $calendar = $calendar->withStatus($status);
        }

        if (!empty($data['bookingAvailability'])) {
            $bookingAvailability = (new BookingAvailabilityDenormalizer())->denormalize(
                $data['bookingAvailability'],
                BookingAvailability::class
            );
            $calendar = $calendar->withBookingAvailability($bookingAvailability);
        }

        return $calendar;
    }

    public function serialize(): array
    {
        $calendar = [
            'type' => $this->calendar->getType()->toString(),
            'status' => (new StatusNormalizer())->normalize($this->calendar->getStatus()),
            'bookingAvailability' => (new BookingAvailabilityNormalizer())->normalize($this->calendar->getBookingAvailability()),
        ];

        if ($this->calendar instanceof CalendarWithSubEvents) {
            $serializedSubEvents = array_map(
                function (SubEvent $subEvent) {
                    return (new SubEventNormalizer())->normalize($subEvent);
                },
                $this->calendar->getSubEvents()->toArray()
            );
            if (!empty($serializedSubEvents)) {
                $calendar['timestamps'] = $serializedSubEvents;
            }
        }

        if ($this->calendar instanceof CalendarWithOpeningHours) {
            $serializedOpeningHours = array_map(
                function (OpeningHour $openingHour) {
                    return (new OpeningHourNormalizer())->normalize($openingHour);
                },
                $this->calendar->getOpeningHours()->toArray()
            );
            if (!empty($serializedOpeningHours)) {
                $calendar['openingHours'] = $serializedOpeningHours;
            }
        }

        if ($this->calendar instanceof CalendarWithClosedDays && !$this->calendar->getClosedDays()->isEmpty()) {
            $closedDayNormalizer = new ClosedDayNormalizer();
            $calendar['openingHoursClosedDays'] = array_map(
                function (ClosedDay $closedDay) use ($closedDayNormalizer) {
                    return $closedDayNormalizer->normalize($closedDay);
                },
                $this->calendar->getClosedDays()->toArray()
            );
        }

        if ($this->calendar instanceof CalendarWithAdjustedDays && !$this->calendar->getAdjustedDays()->isEmpty()) {
            $adjustedDayNormalizer = new AdjustedDayNormalizer();
            $calendar['openingHoursAdjustedDays'] = array_map(
                fn (AdjustedDay $aoh) => $adjustedDayNormalizer->normalize($aoh),
                $this->calendar->getAdjustedDays()->toArray()
            );
        }

        if ($this->calendar instanceof CalendarWithDateRange) {
            $calendar['startDate'] = $this->calendar->getStartDate()->format(DateTimeInterface::ATOM);
            $calendar['endDate'] = $this->calendar->getEndDate()->format(DateTimeInterface::ATOM);
        }

        return $calendar;
    }

    // This deserialization function takes into account old data that might be missing a timezone.
    // It will fall back to creating a DateTime object and assume Brussels.
    // If this still fails an error will be thrown.
    private static function deserializeDateTime(string $dateTimeData): DateTimeImmutable
    {
        $dateTime = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $dateTimeData);

        if ($dateTime === false) {
            $dateTime = DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s', $dateTimeData, new DateTimeZone('Europe/Brussels'));

            if (!$dateTime) {
                throw new InvalidArgumentException('Invalid date string provided for timestamp, ISO8601 expected!');
            }
        }

        return $dateTime;
    }
}
