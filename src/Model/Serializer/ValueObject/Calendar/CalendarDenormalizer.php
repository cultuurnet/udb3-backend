<?php

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvents;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
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
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedStatusReason;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class CalendarDenormalizer implements DenormalizerInterface
{
    /**
     * @var TranslatedStatusReasonDenormalizer
     */
    private $statusReasonDenormalizer;

    public function __construct()
    {
        $this->statusReasonDenormalizer = new TranslatedStatusReasonDenormalizer();
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

        $topLevelStatus = isset($data['status']) ? $this->denormalizeStatus($data['status']) : null;

        switch ($data['calendarType']) {
            case 'single':
                $subEvent = $this->denormalizeSubEvent($data, $topLevelStatus);
                if (isset($data['subEvent'][0])) {
                    $subEvent = $this->denormalizeSubEvent($data['subEvent'][0], $topLevelStatus);
                }
                $calendar = new SingleSubEventCalendar($subEvent);
                break;

            case 'multiple':
                $subEvents = array_map(
                    function (array $subEvent) use ($topLevelStatus) {
                        return $this->denormalizeSubEvent($subEvent, $topLevelStatus);
                    },
                    $data['subEvent']
                );
                $subEvents = new SubEvents(...$subEvents);
                $calendar = new MultipleSubEventsCalendar($subEvents);
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

        if (isset($data['status'])) {
            $calendar = $calendar->withStatus(
                $this->denormalizeStatus($data['status'])
            );
        }

        return $calendar;
    }

    /**
     * @inheritdoc
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === Calendar::class;
    }

    /**
     * @todo Extract to a separate OpeningHoursDenormalizer
     * @param array $openingHoursData
     * @return OpeningHours
     */
    private function denormalizeOpeningHours(array $openingHoursData)
    {
        $openingHours = array_map([$this, 'denormalizeOpeningHour'], $openingHoursData);
        return new OpeningHours(...$openingHours);
    }

    /**
     * @todo Extract to a separate OpeningHourDenormalizer
     * @param array $openingHourData
     * @return OpeningHour
     */
    private function denormalizeOpeningHour(array $openingHourData)
    {
        $days = $this->denormalizeDays($openingHourData['dayOfWeek']);
        $opens = $this->denormalizeTime($openingHourData['opens']);
        $closes = $this->denormalizeTime($openingHourData['closes']);
        return new OpeningHour($days, $opens, $closes);
    }

    /**
     * @todo Extract to a separate DaysDenormalizer
     * @param array $daysData
     * @return Days
     */
    private function denormalizeDays(array $daysData)
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
     * @return Time
     */
    private function denormalizeTime($timeString)
    {
        $dateTime = \DateTimeImmutable::createFromFormat('H:i', $timeString);
        $hour = new Hour((int) $dateTime->format('H'));
        $minute = new Minute((int) $dateTime->format('i'));
        return new Time($hour, $minute);
    }

    private function denormalizeDateRange(array $dateRangeData): DateRange
    {
        $startDate = \DateTimeImmutable::createFromFormat(\DATE_ATOM, $dateRangeData['startDate']);
        $endDate = \DateTimeImmutable::createFromFormat(\DATE_ATOM, $dateRangeData['endDate']);

        return new DateRange($startDate, $endDate);
    }

    private function denormalizeSubEvent(array $subEventData, ?Status $topLevelStatus): SubEvent
    {
        $statusType = $topLevelStatus ? $topLevelStatus->getType() : StatusType::Available();
        $statusReason = $topLevelStatus ? $topLevelStatus->getReason() : null;

        if (isset($subEventData['status']['type'])) {
            $statusType = new StatusType($subEventData['status']['type']);
        }

        if (isset($subEventData['status']['reason'])) {
            /** @var TranslatedStatusReason $statusReason */
            $statusReason = $this->statusReasonDenormalizer->denormalize(
                $subEventData['status']['reason'],
                TranslatedStatusReason::class
            );
        }

        $status = new Status($statusType, $statusReason);

        return new SubEvent(
            $this->denormalizeDateRange($subEventData),
            $status
        );
    }

    private function denormalizeStatus(array $status): Status
    {
        $statusType = new StatusType($status['type']);
        $statusReason = null;

        if (isset($status['reason'])) {
            $statusReason = $this->statusReasonDenormalizer->denormalize(
                $status['reason'],
                TranslatedStatusReason::class
            );
        }

        return new Status($statusType, $statusReason);
    }
}
