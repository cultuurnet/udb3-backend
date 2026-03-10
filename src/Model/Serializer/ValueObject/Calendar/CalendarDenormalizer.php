<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Contact\BookingInfoDenormalizer;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\RemainingCapacityExceedsCapacity;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvents;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\MultipleSubEventsCalendar;
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
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Model\ValueObject\TimeImmutableRange;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class CalendarDenormalizer implements DenormalizerInterface
{
    private StatusDenormalizer $statusDenormalizer;
    private BookingAvailabilityDenormalizer $bookingAvailabilityDenormalizer;
    private BookingInfoDenormalizer $bookingInfoDenormalizer;

    public function __construct()
    {
        $this->statusDenormalizer = new StatusDenormalizer();
        $this->bookingAvailabilityDenormalizer = new BookingAvailabilityDenormalizer();
        $this->bookingInfoDenormalizer = new BookingInfoDenormalizer();
    }

    /**
     * @inheritdoc
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (!$this->supportsDenormalization($data, $class, $format)) {
            throw new UnsupportedException("CalendarDenormalizer does not support {$class}.");
        }

        if (!is_array($data)) {
            throw new UnsupportedException('Calendar data should be an associative array.');
        }

        $openingHoursData = isset($data['openingHours']) ? $data['openingHours'] : [];
        $openingHours = $this->denormalizeOpeningHours($openingHoursData);

        $statusData = $data['status'] ?? ['type' => 'Available'];
        $topLevelStatus = $this->statusDenormalizer->denormalize($statusData, Status::class);

        $bookingAvailabilityData = $data['bookingAvailability'] ?? ['type' => 'Available'];
        $topLevelBookingAvailability = $this->bookingAvailabilityDenormalizer->denormalize($bookingAvailabilityData, BookingAvailability::class);

        $topLevelBookingInfo = new BookingInfo();
        if (isset($data['bookingInfo'])) {
            $topLevelBookingInfo = $this->bookingInfoDenormalizer->denormalize($data['bookingInfo'], BookingInfo::class);
        }

        if (($data['calendarType'] === 'single' || $data['calendarType'] === 'multiple') && isset($data['subEvent'])) {
            $data['calendarType'] = count($data['subEvent']) === 1 ? 'single' : 'multiple';
        }

        switch ($data['calendarType']) {
            case 'single':
                try {
                    if (isset($data['subEvent'][0])) {
                        $subEvent = $this->denormalizeSubEvent($data['subEvent'][0], $topLevelStatus, $topLevelBookingAvailability, $topLevelBookingInfo);
                    } else {
                        $subEvent = $this->denormalizeSubEvent($data, $topLevelStatus, $topLevelBookingAvailability, $topLevelBookingInfo);
                    }
                } catch (RemainingCapacityExceedsCapacity $e) {
                    throw ApiProblem::bodyInvalidData(new SchemaError(
                        '/subEvent/0/bookingAvailability/remainingCapacity',
                        $e->getMessage()
                    ));
                }
                $calendar = new SingleSubEventCalendar($subEvent);
                $calendar = $calendar->withBookingAvailability($topLevelBookingAvailability);
                break;

            case 'multiple':
                if (!isset($data['subEvent'])) {
                    throw new UnsupportedException('Multiple calendar should have at least one subEvent.');
                }

                $denormalizedSubEvents = [];
                $schemaErrors = [];
                foreach ($data['subEvent'] as $index => $subEventData) {
                    try {
                        $denormalizedSubEvents[] = $this->denormalizeSubEvent($subEventData, $topLevelStatus, $topLevelBookingAvailability, $topLevelBookingInfo);
                    } catch (RemainingCapacityExceedsCapacity $e) {
                        $schemaErrors[] = new SchemaError(
                            '/subEvent/' . $index . '/bookingAvailability/remainingCapacity',
                            $e->getMessage()
                        );
                    }
                }
                if (!empty($schemaErrors)) {
                    throw ApiProblem::bodyInvalidData(...$schemaErrors);
                }
                $subEvents = new SubEvents(...$denormalizedSubEvents);
                $calendar = new MultipleSubEventsCalendar($subEvents);
                if ($topLevelBookingAvailability !== null) {
                    $calendar = $calendar->withBookingAvailability($topLevelBookingAvailability);
                }
                break;

            case 'periodic':
                $dateRange = $this->denormalizeDateRange($data);
                $calendar = new PeriodicCalendar($dateRange, $openingHours);
                break;

            case 'permanent':
            default:
                $calendar = new PermanentCalendar($openingHours);
                break;
        }

        if ($topLevelStatus !== null) {
            $calendar = $calendar->withStatus($topLevelStatus);
        }

        return $calendar;
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === Calendar::class;
    }

    /**
     * @todo Extract to a separate OpeningHoursDenormalizer
     */
    private function denormalizeOpeningHours(array $openingHoursData): OpeningHours
    {
        $openingHours = array_map([$this, 'denormalizeOpeningHour'], $openingHoursData);
        return new OpeningHours(...$openingHours);
    }

    /**
     * @todo Extract to a separate OpeningHourDenormalizer
     */
    private function denormalizeOpeningHour(array $openingHourData): OpeningHour
    {
        $days = $this->denormalizeDays($openingHourData['dayOfWeek']);
        $opens = $this->denormalizeTime($openingHourData['opens']);
        $closes = $this->denormalizeTime($openingHourData['closes']);
        return new OpeningHour($days, $opens, $closes);
    }

    /**
     * @todo Extract to a separate DaysDenormalizer
     */
    private function denormalizeDays(array $daysData): Days
    {
        $days = array_map(
            function ($day) {
                return new Day($day);
            },
            $daysData
        );
        return new Days(...$days);
    }

    /**
     *@todo Extract to a separate TimeDenormalizer
     */
    private function denormalizeTime(string $timeString): Time
    {
        $dateTime = DateTimeFactory::fromFormat('H:i', $timeString);
        $hour = new Hour((int) $dateTime->format('H'));
        $minute = new Minute((int) $dateTime->format('i'));
        return new Time($hour, $minute);
    }

    private function denormalizeDateRange(array $dateRangeData): DateRange
    {
        $startDate = DateTimeFactory::fromISO8601($dateRangeData['startDate']);
        $endDate = DateTimeFactory::fromISO8601($dateRangeData['endDate']);

        return new DateRange($startDate, $endDate);
    }

    private function denormalizeSubEvent(
        array $subEventData,
        Status $topLevelStatus,
        BookingAvailability $topLevelBookingAvailability,
        BookingInfo $topLevelBookingInfo = new BookingInfo()
    ): SubEvent {
        if (!isset($subEventData['status']['type'])) {
            $subEventData['status']['type'] = $topLevelStatus->getType()->toString();
        }
        if (!isset($subEventData['status']['reason']) && $reason = $topLevelStatus->getReason()) {
            foreach ($reason->getLanguages() as $language) {
                $subEventData['status']['reason'][$language->getCode()] = $reason->getTranslation($language)->toString();
            }
        }
        if (!isset($subEventData['bookingAvailability']['type'])) {
            $subEventData['bookingAvailability']['type'] = $topLevelBookingAvailability->getType()->toString();
        }
        if (!isset($subEventData['bookingAvailability']['capacity']) && $topLevelBookingAvailability->getCapacity() !== null) {
            $subEventData['bookingAvailability']['capacity'] = $topLevelBookingAvailability->getCapacity();
        }
        if (!isset($subEventData['bookingAvailability']['remainingCapacity']) && $topLevelBookingAvailability->getRemainingCapacity() !== null) {
            $subEventData['bookingAvailability']['remainingCapacity'] = $topLevelBookingAvailability->getRemainingCapacity();
        }

        $status = $this->statusDenormalizer->denormalize($subEventData['status'], Status::class);

        $bookingAvailability = $this->bookingAvailabilityDenormalizer->denormalize(
            $subEventData['bookingAvailability'],
            BookingAvailability::class
        );

        $bookingInfo = $topLevelBookingInfo;
        if (isset($subEventData['bookingInfo'])) {
            $bookingInfo = $this->bookingInfoDenormalizer->denormalize($subEventData['bookingInfo'], BookingInfo::class);
        }

        $subEvent = new SubEvent(
            $this->denormalizeDateRange($subEventData),
            $status,
            $bookingAvailability,
            $bookingInfo,
        );

        $childcareStart = $subEventData['childcareStartTime'] ?? null;
        $childcareEnd = $subEventData['childcareEndTime'] ?? null;
        if ($childcareStart !== null || $childcareEnd !== null) {
            $subEvent = $subEvent->withChildcareTimeRange(new TimeImmutableRange($childcareStart, $childcareEnd));
        }

        return $subEvent;
    }
}
