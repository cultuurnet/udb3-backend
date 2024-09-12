<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
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
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class CalendarDenormalizer implements DenormalizerInterface
{
    private StatusDenormalizer $statusDenormalizer;
    private BookingAvailabilityDenormalizer $bookingAvailabilityDenormalizer;

    public function __construct()
    {
        $this->statusDenormalizer = new StatusDenormalizer();
        $this->bookingAvailabilityDenormalizer = new BookingAvailabilityDenormalizer();
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

        if (($data['calendarType'] === 'single' || $data['calendarType'] === 'multiple') && isset($data['subEvent'])) {
            $data['calendarType'] = count($data['subEvent']) === 1 ? 'single' : 'multiple';
        }

        switch ($data['calendarType']) {
            case 'single':
                if (isset($data['subEvent'][0])) {
                    $subEvent = $this->denormalizeSubEvent($data['subEvent'][0], $topLevelStatus, $topLevelBookingAvailability);
                } else {
                    $subEvent = $this->denormalizeSubEvent($data, $topLevelStatus, $topLevelBookingAvailability);
                }
                $calendar = new SingleSubEventCalendar($subEvent);
                if ($topLevelBookingAvailability !== null) {
                    $calendar = $calendar->withBookingAvailability($topLevelBookingAvailability);
                }
                break;

            case 'multiple':
                if (!isset($data['subEvent'])) {
                    throw new UnsupportedException('Multiple calendar should have at least one subEvent.');
                }

                $subEvents = array_map(
                    function (array $subEvent) use ($topLevelStatus, $topLevelBookingAvailability) {
                        return $this->denormalizeSubEvent($subEvent, $topLevelStatus, $topLevelBookingAvailability);
                    },
                    $data['subEvent']
                );
                $subEvents = new SubEvents(...$subEvents);
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
     * @todo Extract to a separate TimeDenormalizer
     * @param string $timeString
     */
    private function denormalizeTime($timeString): Time
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
        BookingAvailability $topLevelBookingAvailability
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

        $status = $this->statusDenormalizer->denormalize($subEventData['status'], Status::class);

        $bookingAvailability = $this->bookingAvailabilityDenormalizer->denormalize(
            $subEventData['bookingAvailability'],
            BookingAvailability::class
        );

        return new SubEvent(
            $this->denormalizeDateRange($subEventData),
            $status,
            $bookingAvailability
        );
    }
}
