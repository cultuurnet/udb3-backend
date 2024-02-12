<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\EventSourcing\ConvertsToGranularEvents;
use CultuurNet\UDB3\EventSourcing\MainLanguageDefined;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Place\PlaceEvent;
use DateTimeImmutable;
use DateTimeInterface;

final class PlaceCreated extends PlaceEvent implements ConvertsToGranularEvents, MainLanguageDefined
{
    private Language $mainLanguage;
    private string $title;
    private EventType $eventType;
    private Address $address;
    private Calendar $calendar;
    private ?DateTimeImmutable $publicationDate;

    public function __construct(
        string $placeId,
        Language $mainLanguage,
        string $title,
        EventType $eventType,
        Address $address,
        Calendar $calendar,
        ?DateTimeImmutable $publicationDate = null
    ) {
        parent::__construct($placeId);

        $this->mainLanguage = $mainLanguage;
        $this->title = $title;
        $this->eventType = $eventType;
        $this->address = $address;
        $this->calendar = $calendar;
        $this->publicationDate = $publicationDate;
    }

    public function getMainLanguage(): Language
    {
        return $this->mainLanguage;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getEventType(): EventType
    {
        return $this->eventType;
    }

    public function getCalendar(): Calendar
    {
        return $this->calendar;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function getPublicationDate(): ?DateTimeImmutable
    {
        return $this->publicationDate;
    }

    public function toGranularEvents(): array
    {
        return [
            new TitleUpdated($this->placeId, $this->title),
            new TypeUpdated($this->placeId, $this->eventType),
            new AddressUpdated($this->placeId, $this->address),
            new CalendarUpdated($this->placeId, $this->calendar),
        ];
    }

    public function serialize(): array
    {
        $publicationDate = null;
        if (!is_null($this->getPublicationDate())) {
            $publicationDate = $this->getPublicationDate()->format(DateTimeInterface::ATOM);
        }
        return parent::serialize() + [
            'main_language' => $this->mainLanguage->getCode(),
            'title' => $this->getTitle(),
            'event_type' => $this->getEventType()->serialize(),
            'address' => $this->getAddress()->serialize(),
            'calendar' => $this->getCalendar()->serialize(),
            'publication_date' => $publicationDate,
        ];
    }

    public static function deserialize(array $data): PlaceCreated
    {
        $publicationDate = null;
        if (!empty($data['publication_date'])) {
            $publicationDate = DateTimeImmutable::createFromFormat(
                DateTimeInterface::ATOM,
                $data['publication_date']
            );
        }
        return new static(
            $data['place_id'],
            new Language($data['main_language']),
            $data['title'],
            EventType::deserialize($data['event_type']),
            Address::deserialize($data['address']),
            Calendar::deserialize($data['calendar']),
            $publicationDate
        );
    }
}
