<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\EventSourcing\ConvertsToGranularEvents;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;
use CultuurNet\UDB3\Theme;

final class MajorInfoUpdated extends AbstractEvent implements ConvertsToGranularEvents
{
    private string $title;
    private EventType $eventType;
    private ?Theme $theme;
    private LocationId $location;
    private Calendar $calendar;

    public function __construct(
        string $eventId,
        string $title,
        EventType $eventType,
        LocationId $location,
        Calendar $calendar,
        Theme $theme = null
    ) {
        parent::__construct($eventId);

        $this->title = $title;
        $this->eventType = $eventType;
        $this->location = $location;
        $this->calendar = $calendar;
        $this->theme = $theme;
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

    public function toGranularEvents(): array
    {
        return array_values(
            array_filter(
                [
                    new TitleUpdated($this->itemId, $this->title),
                    new TypeUpdated($this->itemId, $this->eventType->toUdb3ModelCategory()),
                    $this->theme ? new ThemeUpdated($this->itemId, $this->theme) : null,
                    new LocationUpdated($this->itemId, $this->location),
                    new CalendarUpdated($this->itemId, $this->calendar),
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
        return parent::serialize() + [
            'title' => $this->getTitle(),
            'event_type' => $this->getEventType()->serialize(),
            'theme' => $theme,
            'location' => $this->getLocation()->toString(),
            'calendar' => $this->getCalendar()->serialize(),
        ];
    }

    public static function deserialize(array $data): MajorInfoUpdated
    {
        return new self(
            $data['item_id'],
            $data['title'],
            EventType::deserialize($data['event_type']),
            new LocationId($data['location']),
            Calendar::deserialize($data['calendar']),
            empty($data['theme']) ? null : Theme::deserialize($data['theme'])
        );
    }
}
