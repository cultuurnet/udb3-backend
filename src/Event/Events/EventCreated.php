<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Event\EventEvent;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\EventSourcing\ConvertsToGranularEvents;
use CultuurNet\UDB3\EventSourcing\MainLanguageDefined;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Theme;
use DateTimeImmutable;
use DateTimeInterface;

final class EventCreated extends EventEvent implements ConvertsToGranularEvents, MainLanguageDefined
{
    private Language $mainLanguage;
    private string $title;
    private EventType $eventType;
    private ?Theme $theme;
    private LocationId $location;
    private Calendar $calendar;
    private ?DateTimeImmutable $publicationDate;

    public function __construct(
        string $eventId,
        Language $mainLanguage,
        string $title,
        EventType $eventType,
        LocationId $location,
        Calendar $calendar,
        ?Theme $theme = null,
        ?DateTimeImmutable $publicationDate = null
    ) {
        parent::__construct($eventId);

        $this->mainLanguage = $mainLanguage;
        $this->title = $title;
        $this->eventType = $eventType;
        $this->location = $location;
        $this->calendar = $calendar;
        $this->theme = $theme;
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

    public function getTheme(): ?Theme
    {
        return $this->theme;
    }

    public function getCalendar(): Calendar
    {
        return $this->calendar;
    }

    public function getLocation(): LocationId
    {
        return $this->location;
    }

    public function getPublicationDate(): ?DateTimeImmutable
    {
        return $this->publicationDate;
    }

    public function toGranularEvents(): array
    {
        return array_values(
            array_filter(
                [
                    new TitleUpdated($this->eventId, $this->title),
                    new TypeUpdated($this->eventId, $this->eventType),
                    $this->theme ? new ThemeUpdated($this->eventId, $this->theme) : null,
                    new LocationUpdated($this->eventId, $this->location),
                    new CalendarUpdated($this->eventId, $this->calendar),
                ]
            )
        );
    }

    public function serialize(): array
    {
        $theme = null;
        if ($this->getTheme() !== null) {
            $theme = $this->getTheme()->serialize();
        }
        $publicationDate = null;
        if (!is_null($this->getPublicationDate())) {
            $publicationDate = $this->getPublicationDate()->format(DateTimeInterface::ATOM);
        }
        return parent::serialize() + [
            'main_language' => $this->mainLanguage->getCode(),
            'title' => $this->getTitle(),
            'event_type' => $this->getEventType()->serialize(),
            'theme' => $theme,
            'location' => $this->getLocation()->toString(),
            'calendar' => $this->getCalendar()->serialize(),
            'publication_date' => $publicationDate,
        ];
    }

    public static function deserialize(array $data): EventCreated
    {
        $theme = null;
        if (!empty($data['theme'])) {
            $theme = Theme::deserialize($data['theme']);
        }
        $publicationDate = null;
        if (!empty($data['publication_date'])) {
            $publicationDate = DateTimeFactory::fromAtom($data['publication_date']);
        }
        return new self(
            $data['event_id'],
            new Language($data['main_language']),
            $data['title'],
            EventType::deserialize($data['event_type']),
            new LocationId($data['location']),
            Calendar::deserialize($data['calendar']),
            $theme,
            $publicationDate
        );
    }
}
