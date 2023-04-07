<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Calendar as LegacyCalendar;
use CultuurNet\UDB3\Event\Events\Moderation\Approved;
use CultuurNet\UDB3\Event\Events\Moderation\Published;
use CultuurNet\UDB3\Event\Events\Moderation\Rejected;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
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
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvents;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;
use CultuurNet\UDB3\SerializableSimpleXmlElement;
use CultuurNet\UDB3\StringLiteral;
use CultuurNet\UDB3\Title as LegacyTitle;
use DateTimeZone;

trait EventFromUDB2
{
    public function toGranularEvents(): array
    {
        $granularEvents = [];
        $eventAsArray = $this->getEventAsArray();
        $details = $eventAsArray['eventdetails'][0]['eventdetail'];

        foreach ($details as $key => $detail) {
            if ($key == 0) {
                $granularEvents[] = new TitleUpdated($this->eventId, new LegacyTitle($detail['title'][0]['_text']));
            } else {
                $granularEvents[] = new TitleTranslated(
                    $this->eventId,
                    new Language($detail['@attributes']['lang']),
                    new LegacyTitle($detail['title'][0]['_text'])
                );
            }
        }

        $categories = $eventAsArray['categories'][0]['category'];

        foreach ($categories as $category) {
            if ($category['@attributes']['type'] === 'eventtype') {
                $granularEvents[] = new TypeUpdated(
                    $this->eventId,
                    new EventType($category['@attributes']['catid'], $category['_text'])
                );
            }
        }

        // Todo: decide how to handle dummy locations & externalIds
        if (isset($eventAsArray['location'][0]['label'][0]['@attributes']['cdbid'])) {
            $granularEvents[] = new LocationUpdated(
                $this->eventId,
                new LocationId($eventAsArray['location'][0]['label'][0]['@attributes']['cdbid'])
            );
        }

        $calendarEvent = $this->getCalendar($eventAsArray['calendar'][0]);
        $granularEvents[] = new CalendarUpdated($this->eventId, LegacyCalendar::fromUdb3ModelCalendar($calendarEvent));

        $moderation = $this->getModeration($eventAsArray['@attributes']['wfstatus']);
        if ($moderation !== null) {
            $granularEvents[] = $moderation;
        }

        return $granularEvents;
    }

    private function getEventAsArray(): array
    {
        $cdbXml = new SerializableSimpleXmlElement(
            $this->cdbXml,
            0,
            false,
            $this->cdbXmlNamespaceUri
        );
        $eventAsArray = $cdbXml->serialize();
        // Some cdbxml have a root node 'cdbxml'
        if (array_key_first($eventAsArray) === 'cdbxml') {
            return $eventAsArray['cdbxml']['event'][0];
        }
        return $eventAsArray['event'];
    }

    private function getCalendar(array $calendarAsArray): Calendar
    {
        $calendarType = array_key_first($calendarAsArray);

        if ($calendarType === 'permanentopeningtimes') {
            $openingHours = $this->getOpeningHours($calendarAsArray['permanentopeningtimes'][0]['permanent'][0]);
            return new PermanentCalendar(new OpeningHours(...$openingHours));
        }

        if ($calendarType === 'periods') {
            $dateRange = new DateRange(
                \DateTimeImmutable::createFromFormat('Y-m-d', $calendarAsArray['periods'][0]['period'][0]['datefrom'][0]['_text']),
                \DateTimeImmutable::createFromFormat('Y-m-d', $calendarAsArray['periods'][0]['period'][0]['dateto'][0]['_text'])
            );

            $openingHours = $this->getOpeningHours($calendarAsArray['periods'][0]['period'][0]);

            return new PeriodicCalendar($dateRange, new OpeningHours(...$openingHours));
        }

        $subEvents = [];

        foreach ($calendarAsArray['timestamps'][0]['timestamp'] as $timeStampAsArray) {
            $timeStart = $timeStampAsArray['timestart'][0]['_text'] ?? '0:00:00';
            $startTime = $timeStampAsArray['date'][0]['_text'] . 'T' . $timeStart;
            $endTime = isset($timeStampAsArray['timeend']) ?
                    $timeStampAsArray['date'][0]['_text'] . 'T' . $timeStampAsArray['timeend'][0]['_text'] :
                    $startTime;
            $subEvents[] = new SubEvent(
                new DateRange(
                    \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s', $startTime, new DateTimeZone('Europe/Brussels')),
                    \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s', $endTime, new DateTimeZone('Europe/Brussels'))
                ),
                new Status(
                    StatusType::Available()
                ),
                new BookingAvailability(
                    BookingAvailabilityType::Available()
                )
            );
        }

        if (count($subEvents) === 1) {
            return new SingleSubEventCalendar($subEvents[0]);
        }

        return new MultipleSubEventsCalendar(new SubEvents(...$subEvents));
    }

    /**
     * @return OpeningHour[]
     */
    private function getOpeningHours(array $openingHoursAsArray): array
    {
        $openingHours = [];
        if (isset($openingHoursAsArray['weekscheme'])) {
            foreach ($openingHoursAsArray['weekscheme'][0] as $dayOfWeek => $hours) {
                if (isset($hours[0]['openingtime'])) {
                    $from = explode(':', $hours[0]['openingtime'][0]['@attributes']['from']);
                    $to = explode(':', $hours[0]['openingtime'][0]['@attributes']['to']);

                    $openingHours[] = new OpeningHour(
                        new Days(new Day($dayOfWeek)),
                        new Time(
                            new Hour((int)$from[0]),
                            new Minute((int)$from[1])
                        ),
                        new Time(
                            new Hour((int)$to[0]),
                            new Minute((int)$to[1])
                        ),
                    );
                }
            }
        }
        return $openingHours;
    }

    public function getDummyLocation(): ?DummyLocation
    {
        $eventAsArray = $this->getEventAsArray();
        if (isset($eventAsArray['location'][0]['label'][0]['@attributes']['cdbid'])) {
            return null;
        }

        $addressAsArray = $eventAsArray['location'][0]['address'][0]['physical'][0];

        return new DummyLocation(
            new Title($eventAsArray['location'][0]['label'][0]['_text']),
            new Address(
                new Street($addressAsArray['street'][0]['_text'] . ' ' . $addressAsArray['housenr'][0]['_text']),
                new PostalCode($addressAsArray['zipcode'][0]['_text']),
                new Locality($addressAsArray['city'][0]['_text']),
                new CountryCode($addressAsArray['country'][0]['_text'])
            )
        );
    }

    public function getExternalId(): ?string
    {
        $eventAsArray = $this->getEventAsArray();
        return $eventAsArray['location'][0]['label'][0]['@attributes']['externalid'] ?? null;
    }

    private function getModeration(?string $wfstatus): ?AbstractEvent
    {
        if ($wfstatus === 'readyforvalidation') {
            return new Published($this->eventId, new \DateTimeImmutable());
        }

        if ($wfstatus === 'approved') {
            return new Approved($this->eventId);
        }

        if ($wfstatus === 'rejected') {
            return new Rejected($this->eventId, new StringLiteral(''));
        }

        if ($wfstatus === 'deleted') {
            return new EventDeleted($this->eventId);
        }

        return null;
    }
}
