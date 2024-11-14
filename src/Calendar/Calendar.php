<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Calendar;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\JsonLdSerializableInterface;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\BookingAvailabilityDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\BookingAvailabilityNormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\OpeningHourDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\OpeningHourNormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\StatusDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\StatusNormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\SubEventDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\SubEventNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar as Udb3ModelCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarWithOpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarWithSubEvents;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PeriodicCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Offer\CalendarTypeNotSupported;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

/**
 * @deprecated
 *   Use concrete implementations of CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar instead where possible.
 */
final class Calendar implements CalendarInterface, JsonLdSerializableInterface, Serializable
{
    /**
     * Store the CalendarType as a string to prevent serialization issues when the Calendar is part of a command that
     * gets queued in Redis, as the base Enum class that it extends from does not support serialization for some reason.
     */
    private string $type;

    private ?DateTimeInterface $startDate;

    private ?DateTimeInterface $endDate;

    private array $subEvents ;

    private array $openingHours ;

    private Status $status;

    private BookingAvailability $bookingAvailability;

    /**
     * @param SubEvent[] $subEvents
     * @param OpeningHour[] $openingHours
     */
    public function __construct(
        CalendarType $type,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        array $subEvents = [],
        array $openingHours = []
    ) {
        if (empty($subEvents) && ($type->sameAs(CalendarType::single()) || $type->sameAs(CalendarType::multiple()))) {
            throw new \UnexpectedValueException('A single or multiple calendar should have sub events.');
        }

        if (($startDate === null || $endDate === null) && $type->sameAs(CalendarType::periodic())) {
            throw new \UnexpectedValueException('A period should have a start- and end-date.');
        }

        foreach ($subEvents as $subEvent) {
            if (!is_a($subEvent, SubEvent::class)) {
                throw new \InvalidArgumentException('SubEvents should have type SubEvent.');
            }
        }

        foreach ($openingHours as $openingHour) {
            if (!is_a($openingHour, OpeningHour::class)) {
                throw new \InvalidArgumentException('OpeningHours should have type OpeningHour.');
            }
        }

        $this->type = $type->toString();
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->openingHours = $openingHours;

        usort($subEvents, function (SubEvent $subEvent, SubEvent $otherSubEvent) {
            return $subEvent->getDateRange()->getFrom() <=> $otherSubEvent->getDateRange()->getFrom();
        });

        $this->subEvents = $subEvents;

        $this->status = new Status($this->deriveStatusTypeFromSubEvents(), null);

        $this->bookingAvailability = $this->deriveBookingAvailabilityFromSubEvents();
    }

    public function withStatus(Status $status): self
    {
        $clone = clone $this;
        $clone->status = $status;
        return $clone;
    }

    private function guardUpdatingBookingAvailability(): void
    {
        if ($this->getType()->sameAs(CalendarType::periodic()) || $this->getType()->sameAs(CalendarType::permanent())) {
            throw CalendarTypeNotSupported::forCalendarType($this->getType());
        }
    }

    public function withBookingAvailability(BookingAvailability $bookingAvailability): self
    {
        $this->guardUpdatingBookingAvailability();

        $clone = clone $this;
        $clone->bookingAvailability = $bookingAvailability;
        return $clone;
    }

    public function withStatusOnSubEvents(Status $status): self
    {
        $clone = clone $this;
        $clone->subEvents = \array_map(
            function (SubEvent $subEvent) use ($status): SubEvent {
                return $subEvent->withStatus($status);
            },
            $clone->getSubEvents()
        );
        return $clone;
    }

    public function withBookingAvailabilityOnSubEvents(BookingAvailability $bookingAvailability): self
    {
        $this->guardUpdatingBookingAvailability();

        $clone = clone $this;
        $clone->subEvents = \array_map(
            function (SubEvent $subEvent) use ($bookingAvailability): SubEvent {
                return $subEvent->withBookingAvailability($bookingAvailability);
            },
            $clone->getSubEvents()
        );
        return $clone;
    }

    public function getType(): CalendarType
    {
        return new CalendarType($this->type);
    }

    public function getStartDate(): ?DateTimeInterface
    {
        $subEvents = $this->getSubEvents();

        if (empty($subEvents)) {
            return $this->startDate;
        }

        $startDate = null;
        foreach ($subEvents as $subEvent) {
            if ($startDate === null || $subEvent->getDateRange()->getFrom() < $startDate) {
                $startDate = $subEvent->getDateRange()->getFrom();
            }
        }

        return $startDate;
    }

    public function getEndDate(): ?DateTimeInterface
    {
        $subEvents = $this->getSubEvents();

        if (empty($subEvents)) {
            return $this->endDate;
        }

        $endDate = null;
        foreach ($this->getSubEvents() as $subEvent) {
            if ($endDate === null || $subEvent->getDateRange()->getTo() > $endDate) {
                $endDate = $subEvent->getDateRange()->getTo();
            }
        }

        return $endDate;
    }

    /**
     * @return array|OpeningHour[]
     */
    public function getOpeningHours(): array
    {
        return $this->openingHours;
    }

    /**
     * @return array|SubEvent[]
     */
    public function getSubEvents(): array
    {
        return $this->subEvents;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getBookingAvailability(): BookingAvailability
    {
        return $this->bookingAvailability;
    }

    public function serialize(): array
    {
        $serializedSubEvents = array_map(
            function (SubEvent $subEvent) {
                return (new SubEventNormalizer())->normalize($subEvent);
            },
            $this->subEvents
        );

        $serializedOpeningHours = array_map(
            function (OpeningHour $openingHour) {
                return (new OpeningHourNormalizer())->normalize($openingHour);
            },
            $this->openingHours
        );

        $calendar = [
            'type' => $this->type,
            'status' => (new StatusNormalizer())->normalize($this->status),
            'bookingAvailability' => (new BookingAvailabilityNormalizer())->normalize($this->bookingAvailability),
        ];

        empty($this->startDate) ?: $calendar['startDate'] = $this->startDate->format(DateTimeInterface::ATOM);
        empty($this->endDate) ?: $calendar['endDate'] = $this->endDate->format(DateTimeInterface::ATOM);
        empty($serializedSubEvents) ?: $calendar['timestamps'] = $serializedSubEvents;
        empty($serializedOpeningHours) ?: $calendar['openingHours'] = $serializedOpeningHours;

        return $calendar;
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
        $defaultSubEvents = [];
        if ($calendarType->sameAs(CalendarType::single()) || $calendarType->sameAs(CalendarType::multiple())) {
            if ($startDate) {
                $defaultSubEvents[] = new SubEvent(
                    new DateRange(
                        \DateTimeImmutable::createFromMutable($startDate),
                        $endDate ? \DateTimeImmutable::createFromMutable($endDate) : \DateTimeImmutable::createFromMutable($startDate)
                    ),
                    new Status(StatusType::Available()),
                    new BookingAvailability(BookingAvailabilityType::Available())
                );
            }
        }

        $calendar = new self(
            $calendarType,
            $startDate,
            $endDate,
            !empty($data['timestamps']) ? array_map(
                function ($subEvent) {
                    return (new SubEventDenormalizer())->denormalize($subEvent, SubEvent::class);
                },
                $data['timestamps']
            ) : $defaultSubEvents,
            !empty($data['openingHours']) ? array_map(
                function ($openingHour) {
                    return (new OpeningHourDenormalizer())->denormalize($openingHour, OpeningHour::class);
                },
                $data['openingHours']
            ) : []
        );

        if (!empty($data['status'])) {
            $calendar->status = (new StatusDenormalizer())->denormalize($data['status'], Status::class);
        }

        if (!empty($data['bookingAvailability'])) {
            $calendar->bookingAvailability = (new BookingAvailabilityDenormalizer())->denormalize(
                $data['bookingAvailability'],
                BookingAvailability::class
            );
        }

        return $calendar;
    }

    /**
     * This deserialization function takes into account old data that might be missing a timezone.
     * It will fall back to creating a DateTime object and assume Brussels.
     * If this still fails an error will be thrown.
     */
    private static function deserializeDateTime(string $dateTimeData): DateTime
    {
        $dateTime = DateTime::createFromFormat(DateTimeInterface::ATOM, $dateTimeData);

        if ($dateTime === false) {
            $dateTime = DateTime::createFromFormat('Y-m-d\TH:i:s', $dateTimeData, new DateTimeZone('Europe/Brussels'));

            if (!$dateTime) {
                throw new InvalidArgumentException('Invalid date string provided for timestamp, ISO8601 expected!');
            }
        }

        return $dateTime;
    }

    public function toJsonLd(): array
    {
        $jsonLd = [];

        $jsonLd['calendarType'] = $this->type;

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();
        if ($startDate !== null) {
            $jsonLd['startDate'] = $startDate->format(DateTimeInterface::ATOM);
        }
        if ($endDate !== null) {
            $jsonLd['endDate'] = $endDate->format(DateTimeInterface::ATOM);
        }

        $jsonLd['status'] = (new StatusNormalizer())->normalize($this->determineCorrectTopStatusForProjection());

        $jsonLd['bookingAvailability'] = (new BookingAvailabilityNormalizer())->normalize($this->determineCorrectTopBookingAvailabilityForProjection());

        $subEvents = array_values($this->getSubEvents());
        if (!empty($subEvents)) {
            $jsonLd['subEvent'] = [];
            foreach ($subEvents as $id => $subEvent) {
                $jsonLdSubEvent = (new SubEventNormalizer())->normalize($subEvent);
                $jsonLdSubEvent['@type'] = 'Event';

                $jsonLd['subEvent'][] = ['id' => $id] + $jsonLdSubEvent;
            }
        }

        $openingHours = $this->getOpeningHours();
        if (!empty($openingHours)) {
            $jsonLd['openingHours'] = [];
            foreach ($openingHours as $openingHour) {
                $jsonLd['openingHours'][] = (new OpeningHourNormalizer())->normalize($openingHour);
            }
        }

        return $jsonLd;
    }

    public function sameAs(Calendar $otherCalendar): bool
    {
        return $this->toJsonLd() === $otherCalendar->toJsonLd();
    }

    public static function single(
        SubEvent $subEvent,
        ?Status $status = null,
        ?BookingAvailability $bookingAvailability = null
    ): self {
        $calendar = new self(CalendarType::single(), null, null, [$subEvent]);
        if ($status) {
            $calendar = $calendar->withStatus($status);
        }
        if ($bookingAvailability) {
            $calendar = $calendar->withBookingAvailability($bookingAvailability);
        }
        return $calendar;
    }

    /**
     * @param SubEvent[] $subEvents
     */
    public static function multiple(
        array $subEvents,
        ?Status $status = null,
        ?BookingAvailability $bookingAvailability = null
    ): self {
        $calendar = new self(CalendarType::multiple(), null, null, $subEvents);
        if ($status) {
            $calendar = $calendar->withStatus($status);
        }
        if ($bookingAvailability) {
            $calendar = $calendar->withBookingAvailability($bookingAvailability);
        }
        return $calendar;
    }

    public static function periodic(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        array $openingHours = [],
        ?Status $status = null
    ): self {
        $calendar = new self(CalendarType::periodic(), $startDate, $endDate, [], $openingHours);
        if ($status) {
            $calendar = $calendar->withStatus($status);
        }
        return $calendar;
    }

    public static function permanent(
        array $openingHours = [],
        ?Status $status = null
    ): self {
        $calendar = new self(CalendarType::permanent(), null, null, [], $openingHours);
        if ($status) {
            $calendar = $calendar->withStatus($status);
        }
        return $calendar;
    }

    public static function fromUdb3ModelCalendar(Udb3ModelCalendar $udb3Calendar): Calendar
    {
        $type = new CalendarType($udb3Calendar->getType()->toString());

        $startDate = null;
        $endDate = null;
        $subEvents = [];
        $openingHours = [];

        if ($udb3Calendar instanceof PeriodicCalendar) {
            $startDate = $udb3Calendar->getStartDate();
            $endDate = $udb3Calendar->getEndDate();
        }

        if ($udb3Calendar instanceof CalendarWithSubEvents) {
            $subEvents = $udb3Calendar->getSubEvents()->toArray();
        }

        if ($udb3Calendar instanceof CalendarWithOpeningHours) {
            $openingHours = $udb3Calendar->getOpeningHours()->toArray();
        }

        $calendar = new self($type, $startDate, $endDate, $subEvents, $openingHours);

        $topStatus = $udb3Calendar->getStatus();
        $topBookingAvailability = $udb3Calendar->getBookingAvailability();

        if ($type->sameAs(CalendarType::periodic()) || $type->sameAs(CalendarType::permanent())) {
            // If there are no subEvents, set the top status and top bookingAvailability.
            $calendar->status = $topStatus;
            $calendar->bookingAvailability = $topBookingAvailability;
        } elseif ($calendar->deriveStatusTypeFromSubEvents()->sameAs($topStatus->getType())) {
            // If there are subEvents, the top status and bookingAvailability have already been determined by their
            // respective status and bookingAvailability and it should not be overwritten to avoid confusion in the
            // expected behavior in tests, even though the JSON-LD projections will always fix it (again).
            // Only overwrite the top status if it's the same type as the derived status, so the top status reason (if
            // any) is set correctly. This is in line with the logic in determineCorrectTopStatusForProjection().
            $calendar->status = $topStatus;
        }

        return $calendar;
    }

    private function deriveStatusTypeFromSubEvents(): StatusType
    {
        $statusTypeCounts = [];
        $statusTypeCounts[StatusType::Available()->toString()] = 0;
        $statusTypeCounts[StatusType::TemporarilyUnavailable()->toString()] = 0;
        $statusTypeCounts[StatusType::Unavailable()->toString()] = 0;

        foreach ($this->subEvents as $subEvent) {
            ++$statusTypeCounts[$subEvent->getStatus()->getType()->toString()];
        }

        if ($statusTypeCounts[StatusType::Available()->toString()] > 0) {
            return StatusType::Available();
        }

        if ($statusTypeCounts[StatusType::TemporarilyUnavailable()->toString()] > 0) {
            return StatusType::TemporarilyUnavailable();
        }

        if ($statusTypeCounts[StatusType::Unavailable()->toString()] > 0) {
            return StatusType::Unavailable();
        }

        // This extra return is needed for events with calendar type of permanent or periodic.
        return StatusType::Available();
    }

    /**
     * If the calendar has subEvents and a status manually set through an import or full calendar update
     * through the API, the top status might be incorrect.
     * For example the top status can not be Available if all the subEvents are Unavailable or TemporarilyUnavailable.
     * However we want to be flexible in what we accept from API clients since otherwise they will have to implement a
     * lot of (new) logic to make sure the top status they're sending is correct.
     * So we accept the top status as-is, and correct it during projection.
     * That way if the correction is bugged, we can always fix it and replay it with the original data.
     */
    private function determineCorrectTopStatusForProjection(): Status
    {
        // If the calendar has no subEvents, the top level status is always valid.
        if (empty($this->subEvents)) {
            return $this->status;
        }

        // If the calendar has subEvents, the top level status is valid if it is the same type as the type derived from
        // the subEvents. In that case return $this->status so we include the top-level reason (if it has one).
        $expectedStatusType = $this->deriveStatusTypeFromSubEvents();
        if ($this->status->getType()->toString() === $expectedStatusType->toString()) {
            // Also make sure to include the reason of a sub event when there is no reason on the top level.
            if (count($this->subEvents) === 1 && $this->status->getReason() === null) {
                return $this->subEvents[0]->getStatus();
            }

            return $this->status;
        }

        // If the top-level status is invalid compared to the status type derived from the subEvents, return the
        // expected status type without any reason. (If the top level status had a reason it's probably not applicable
        // for the new status type.)
        return new Status($expectedStatusType, null);
    }

    /**
     * This method can determine the top level booking availability from the sub events
     * - For a periodic or permanent calendar this is always available
     * - If one of the sub events is available then the top level is available
     * - If all of the sub events are unavailable the top level is also unavailable
     */
    private function deriveBookingAvailabilityFromSubEvents(): BookingAvailability
    {
        if (empty($this->subEvents)) {
            return BookingAvailability::Available();
        }

        foreach ($this->subEvents as $subEvent) {
            if ($subEvent->getBookingAvailability()->getType()->sameAs(BookingAvailabilityType::Available())) {
                return BookingAvailability::Available();
            }
        }

        return BookingAvailability::Unavailable();
    }

    /**
     * A projection can require a potential fix:
     * - For a periodic or permanent calendar this is always available
     * - If there are sub events the top level status is calculated
     */
    private function determineCorrectTopBookingAvailabilityForProjection(): BookingAvailability
    {
        if (empty($this->subEvents)) {
            return BookingAvailability::Available();
        }

        return $this->deriveBookingAvailabilityFromSubEvents();
    }
}
