<?php

namespace CultuurNet\UDB3;

use Broadway\Serializer\SerializableInterface;
use CultuurNet\UDB3\Calendar\OpeningHour;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar as Udb3ModelCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarWithDateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarWithOpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarWithSubEvents;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour as Udb3ModelOpeningHour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

final class Calendar implements CalendarInterface, JsonLdSerializableInterface, SerializableInterface
{
    /**
     * @var CalendarType
     */
    protected $type;

    /**
     * @var DateTimeInterface
     */
    protected $startDate;

    /**
     * @var DateTimeInterface
     */
    protected $endDate;

    /**
     * @var Timestamp[]
     */
    protected $timestamps = [];

    /**
     * @var OpeningHour[]
     */
    protected $openingHours = [];

    /**
     * @var Status
     */
    protected $status;

    /**
     * @param CalendarType $type
     * @param DateTimeInterface|null $startDate
     * @param DateTimeInterface|null $endDate
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
        if (empty($timestamps) && ($type->is(CalendarType::SINGLE()) || $type->is(CalendarType::MULTIPLE()))) {
            throw new \UnexpectedValueException('A single or multiple calendar should have timestamps.');
        }

        if (($startDate === null || $endDate === null) && $type->is(CalendarType::PERIODIC())) {
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

        $this->type = $type->toNative();
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->openingHours = $openingHours;

        usort($timestamps, function (Timestamp $timestamp, Timestamp $otherTimestamp) {
            return $timestamp->getStartDate() <=> $otherTimestamp->getStartDate();
        });

        $this->timestamps = $timestamps;

        $this->status = new Status($this->deriveStatusTypeFromSubEvents(), []);
    }

    public function withStatus(Status $status): self
    {
        $clone = clone $this;
        $clone->status = $status;
        return $clone;
    }

    public function withStatusOnTimestamps(Status $status): self
    {
        $clone = clone $this;
        $clone->timestamps = \array_map(
            function (Timestamp $timestamp) use ($status) : Timestamp {
                return $timestamp->withStatus($status);
            },
            $clone->getTimestamps()
        );
        return $clone;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getType(): CalendarType
    {
        return CalendarType::fromNative($this->type);
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
            'status' => $this->status->serialize(),
        ];

        empty($this->startDate) ?: $calendar['startDate'] = $this->startDate->format(DateTime::ATOM);
        empty($this->endDate) ?: $calendar['endDate'] = $this->endDate->format(DateTime::ATOM);
        empty($serializedTimestamps) ?: $calendar['timestamps'] = $serializedTimestamps;
        empty($serializedOpeningHours) ?: $calendar['openingHours'] = $serializedOpeningHours;

        return $calendar;
    }

    public static function deserialize(array $data): Calendar
    {
        $calendarType = CalendarType::fromNative($data['type']);

        // Backwards compatibility for serialized single or multiple calendar types that are missing timestamps but do
        // have a start and end date.
        $defaultTimeStamps = [];
        if ($calendarType->sameValueAs(CalendarType::SINGLE()) || $calendarType->sameValueAs(CalendarType::MULTIPLE())) {
            $defaultTimeStampStartDate = !empty($data['startDate']) ? self::deserializeDateTime($data['startDate']) : null;
            $defaultTimeStampEndDate = !empty($data['endDate']) ? self::deserializeDateTime($data['endDate']) : $defaultTimeStampStartDate;
            $defaultTimeStamp = $defaultTimeStampStartDate && $defaultTimeStampEndDate ? new Timestamp($defaultTimeStampStartDate, $defaultTimeStampEndDate) : null;
            $defaultTimeStamps = $defaultTimeStamp ? [$defaultTimeStamp] : [];
        }

        $calendar = new self(
            $calendarType,
            !empty($data['startDate']) ? self::deserializeDateTime($data['startDate']) : null,
            !empty($data['endDate']) ? self::deserializeDateTime($data['endDate']) : null,
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
            $calendar->status = Status::deserialize($data['status']);
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
        $dateTime = DateTime::createFromFormat(DateTime::ATOM, $dateTimeData);

        if ($dateTime === false) {
            $dateTime = DateTime::createFromFormat('Y-m-d\TH:i:s', $dateTimeData, new DateTimeZone('Europe/Brussels'));

            if (!$dateTime) {
                throw new InvalidArgumentException('Invalid date string provided for timestamp, ISO8601 expected!');
            }
        }

        return $dateTime;
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

    private function deriveStatusTypeFromSubEvents(): StatusType
    {
        $statusTypeCounts = [];
        $statusTypeCounts[StatusType::available()->toNative()] = 0;
        $statusTypeCounts[StatusType::temporarilyUnavailable()->toNative()] = 0;
        $statusTypeCounts[StatusType::unavailable()->toNative()] = 0;

        foreach ($this->timestamps as $timestamp) {
            ++$statusTypeCounts[$timestamp->getStatus()->getType()->toNative()];
        }

        if ($statusTypeCounts[StatusType::available()->toNative()] > 0) {
            return StatusType::available();
        }

        if ($statusTypeCounts[StatusType::temporarilyUnavailable()->toNative()] > 0) {
            return StatusType::temporarilyUnavailable();
        }

        if ($statusTypeCounts[StatusType::unavailable()->toNative()] > 0) {
            return StatusType::unavailable();
        }

        // This extra return is needed for events with calendar type of permanent or periodic.
        return StatusType::available();
    }

    public function toJsonLd(): array
    {
        $jsonLd = [];

        $jsonLd['calendarType'] = $this->getType()->toNative();

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();
        if ($startDate !== null) {
            $jsonLd['startDate'] = $startDate->format(DateTime::ATOM);
        }
        if ($endDate !== null) {
            $jsonLd['endDate'] = $endDate->format(DateTime::ATOM);
        }

        $jsonLd['status'] = $this->determineCorrectTopStatusForProjection()->serialize();

        $timestamps = $this->getTimestamps();
        if (!empty($timestamps)) {
            $jsonLd['subEvent'] = [];
            foreach ($timestamps as $timestamp) {
                $jsonLd['subEvent'][] = $timestamp->toJsonLd();
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

    public static function fromUdb3ModelCalendar(Udb3ModelCalendar $udb3Calendar): Calendar
    {
        $type = CalendarType::fromNative($udb3Calendar->getType()->toString());

        $startDate = null;
        $endDate = null;
        $timestamps = [];
        $openingHours = [];

        if ($udb3Calendar instanceof CalendarWithDateRange) {
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
        $calendar->status = Status::fromUdb3ModelStatus($udb3Calendar->getStatus());
        return $calendar;
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
        if ($this->status->getType()->equals($expectedStatusType)) {
            return $this->status;
        }

        // If the top-level status is invalid compared to the status type derived from the subEvents, return the
        // expected status type without any reason. (If the top level status had a reason it's probably not applicable
        // for the new status type.)
        return new Status($expectedStatusType, []);
    }
}
