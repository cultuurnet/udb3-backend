<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Calendar;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\JsonLdSerializableInterface;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\StatusDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\StatusNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar as Udb3ModelCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarWithOpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarWithSubEvents;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour as Udb3ModelOpeningHour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PeriodicCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Offer\CalendarTypeNotSupported;
use CultuurNet\UDB3\Offer\ValueObjects\BookingAvailability;
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

    private array $timestamps ;

    private array $openingHours ;

    private Status $status;

    private BookingAvailability $bookingAvailability;

    /**
     * @param Timestamp[] $timestamps
     * @param OpeningHour[] $openingHours
     */
    public function __construct(
        CalendarType $type,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        array $timestamps = [],
        array $openingHours = []
    ) {
        if (empty($timestamps) && ($type->sameAs(CalendarType::SINGLE()) || $type->sameAs(CalendarType::MULTIPLE()))) {
            throw new \UnexpectedValueException('A single or multiple calendar should have timestamps.');
        }

        if (($startDate === null || $endDate === null) && $type->sameAs(CalendarType::PERIODIC())) {
            throw new \UnexpectedValueException('A period should have a start- and end-date.');
        }

        foreach ($timestamps as $timestamp) {
            if (!is_a($timestamp, Timestamp::class)) {
                throw new \InvalidArgumentException('Timestamps should have type TimeStamp.');
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

        usort($timestamps, function (Timestamp $timestamp, Timestamp $otherTimestamp) {
            return $timestamp->getStartDate() <=> $otherTimestamp->getStartDate();
        });

        $this->timestamps = $timestamps;

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
        if ($this->getType()->sameAs(CalendarType::PERIODIC()) || $this->getType()->sameAs(CalendarType::PERMANENT())) {
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

    public function withStatusOnTimestamps(Status $status): self
    {
        $clone = clone $this;
        $clone->timestamps = \array_map(
            function (Timestamp $timestamp) use ($status): Timestamp {
                return $timestamp->withStatus($status);
            },
            $clone->getTimestamps()
        );
        return $clone;
    }

    public function withBookingAvailabilityOnTimestamps(BookingAvailability $bookingAvailability): self
    {
        $this->guardUpdatingBookingAvailability();

        $clone = clone $this;
        $clone->timestamps = \array_map(
            function (Timestamp $timestamp) use ($bookingAvailability): Timestamp {
                return $timestamp->withBookingAvailability($bookingAvailability);
            },
            $clone->getTimestamps()
        );
        return $clone;
    }

    public function getType(): CalendarType
    {
        return new CalendarType($this->type);
    }

    public function getStartDate(): ?DateTimeInterface
    {
        $timestamps = $this->getTimestamps();

        if (empty($timestamps)) {
            return $this->startDate;
        }

        $startDate = null;
        foreach ($timestamps as $timestamp) {
            if ($startDate === null || $timestamp->getStartDate() < $startDate) {
                $startDate = $timestamp->getStartDate();
            }
        }

        return $startDate;
    }

    public function getEndDate(): ?DateTimeInterface
    {
        $timestamps = $this->getTimestamps();

        if (empty($timestamps)) {
            return $this->endDate;
        }

        $endDate = null;
        foreach ($this->getTimestamps() as $timestamp) {
            if ($endDate === null || $timestamp->getEndDate() > $endDate) {
                $endDate = $timestamp->getEndDate();
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
     * @return array|Timestamp[]
     */
    public function getTimestamps(): array
    {
        return $this->timestamps;
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
        $serializedTimestamps = array_map(
            function (Timestamp $timestamp) {
                return $timestamp->serialize();
            },
            $this->timestamps
        );

        $serializedOpeningHours = array_map(
            function (OpeningHour $openingHour) {
                return $openingHour->serialize();
            },
            $this->openingHours
        );

        $calendar = [
            'type' => $this->type,
            'status' => (new StatusNormalizer())->normalize($this->status),
            'bookingAvailability' => $this->bookingAvailability->serialize(),
        ];

        empty($this->startDate) ?: $calendar['startDate'] = $this->startDate->format(DateTimeInterface::ATOM);
        empty($this->endDate) ?: $calendar['endDate'] = $this->endDate->format(DateTimeInterface::ATOM);
        empty($serializedTimestamps) ?: $calendar['timestamps'] = $serializedTimestamps;
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

        // Backwards compatibility for serialized single or multiple calendar types that are missing timestamps but do
        // have a start and end date.
        $defaultTimeStamps = [];
        if ($calendarType->sameAs(CalendarType::SINGLE()) || $calendarType->sameAs(CalendarType::MULTIPLE())) {
            $defaultTimeStamps = $startDate ? [new Timestamp($startDate, $endDate ?: $startDate)] : [];
        }

        $calendar = new self(
            $calendarType,
            $startDate,
            $endDate,
            !empty($data['timestamps']) ? array_map(
                function ($timestamp) {
                    return Timestamp::deserialize($timestamp);
                },
                $data['timestamps']
            ) : $defaultTimeStamps,
            !empty($data['openingHours']) ? array_map(
                function ($openingHour) {
                    return OpeningHour::deserialize($openingHour);
                },
                $data['openingHours']
            ) : []
        );

        if (!empty($data['status'])) {
            $calendar->status = (new StatusDenormalizer())->denormalize($data['status'], Status::class);
        }

        if (!empty($data['bookingAvailability'])) {
            $calendar->bookingAvailability = BookingAvailability::deserialize($data['bookingAvailability']);
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

        $jsonLd['bookingAvailability'] = $this->determineCorrectTopBookingAvailabilityForProjection()->serialize();

        $timestamps = array_values($this->getTimestamps());
        if (!empty($timestamps)) {
            $jsonLd['subEvent'] = [];
            foreach ($timestamps as $id => $timestamp) {
                $jsonLd['subEvent'][] = ['id' => $id] + $timestamp->toJsonLd();
            }
        }

        $openingHours = $this->getOpeningHours();
        if (!empty($openingHours)) {
            $jsonLd['openingHours'] = [];
            foreach ($openingHours as $openingHour) {
                $jsonLd['openingHours'][] = $openingHour->serialize();
            }
        }

        return $jsonLd;
    }

    public function sameAs(Calendar $otherCalendar): bool
    {
        return $this->toJsonLd() === $otherCalendar->toJsonLd();
    }

    public static function single(
        Timestamp $timestamp,
        ?Status $status = null,
        ?BookingAvailability $bookingAvailability = null
    ): self {
        $calendar = new self(CalendarType::SINGLE(), null, null, [$timestamp]);
        if ($status) {
            $calendar = $calendar->withStatus($status);
        }
        if ($bookingAvailability) {
            $calendar = $calendar->withBookingAvailability($bookingAvailability);
        }
        return $calendar;
    }

    /**
     * @param Timestamp[] $timestamps
     */
    public static function multiple(
        array $timestamps,
        ?Status $status = null,
        ?BookingAvailability $bookingAvailability = null
    ): self {
        $calendar = new self(CalendarType::MULTIPLE(), null, null, $timestamps);
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
        $calendar = new self(CalendarType::PERIODIC(), $startDate, $endDate, [], $openingHours);
        if ($status) {
            $calendar = $calendar->withStatus($status);
        }
        return $calendar;
    }

    public static function permanent(
        array $openingHours = [],
        ?Status $status = null
    ): self {
        $calendar = new self(CalendarType::PERMANENT(), null, null, [], $openingHours);
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
        $timestamps = [];
        $openingHours = [];

        if ($udb3Calendar instanceof PeriodicCalendar) {
            $startDate = $udb3Calendar->getStartDate();
            $endDate = $udb3Calendar->getEndDate();
        }

        if ($udb3Calendar instanceof CalendarWithSubEvents) {
            $timestamps = array_map(
                function (SubEvent $subEvent) {
                    return Timestamp::fromUdb3ModelSubEvent($subEvent);
                },
                $udb3Calendar->getSubEvents()->toArray()
            );
        }

        if ($udb3Calendar instanceof CalendarWithOpeningHours) {
            $openingHours = array_map(
                function (Udb3ModelOpeningHour $openingHour) {
                    return OpeningHour::fromUdb3ModelOpeningHour($openingHour);
                },
                $udb3Calendar->getOpeningHours()->toArray()
            );
        }

        $calendar = new self($type, $startDate, $endDate, $timestamps, $openingHours);

        $topStatus = $udb3Calendar->getStatus();
        $topBookingAvailability = BookingAvailability::fromUdb3ModelBookingAvailability(
            $udb3Calendar->getBookingAvailability()
        );

        if ($type->sameAs(CalendarType::PERIODIC()) || $type->sameAs(CalendarType::PERMANENT())) {
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

        foreach ($this->timestamps as $timestamp) {
            ++$statusTypeCounts[$timestamp->getStatus()->getType()->toString()];
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
     * If the calendar has subEvents (timestamps), and a status manually set through an import or full calendar update
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
        if (empty($this->timestamps)) {
            return $this->status;
        }

        // If the calendar has subEvents, the top level status is valid if it is the same type as the type derived from
        // the subEvents. In that case return $this->status so we include the top-level reason (if it has one).
        $expectedStatusType = $this->deriveStatusTypeFromSubEvents();
        if ($this->status->getType()->toString() === $expectedStatusType->toString()) {
            // Also make sure to include the reason of a sub event when there is no reason on the top level.
            if (count($this->timestamps) === 1 && $this->status->getReason() === null) {
                return $this->timestamps[0]->getStatus();
            }

            return $this->status;
        }

        // If the top-level status is invalid compared to the status type derived from the subEvents, return the
        // expected status type without any reason. (If the top level status had a reason it's probably not applicable
        // for the new status type.)
        return new Status($expectedStatusType, null);
    }

    /**
     * This method can determine the top level booking availability from the sub events aka timestamps
     * - For a periodic or permanent calendar this is always available
     * - If one of the timestamps is available then the top level is available
     * - If all of the timestamps are unavailable the top level is also unavailable
     */
    private function deriveBookingAvailabilityFromSubEvents(): BookingAvailability
    {
        if (empty($this->timestamps)) {
            return BookingAvailability::available();
        }

        foreach ($this->timestamps as $timestamp) {
            if ($timestamp->getBookingAvailability()->equals(BookingAvailability::available())) {
                return BookingAvailability::available();
            }
        }

        return BookingAvailability::unavailable();
    }

    /**
     * A projection can require a potential fix:
     * - For a periodic or permanent calendar this is always available
     * - If there are timestamps the top level status is calculated
     */
    private function determineCorrectTopBookingAvailabilityForProjection(): BookingAvailability
    {
        if (empty($this->timestamps)) {
            return BookingAvailability::available();
        }

        return $this->deriveBookingAvailabilityFromSubEvents();
    }
}
