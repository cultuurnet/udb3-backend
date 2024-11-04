<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use DateTimeImmutable;

class CreateEvent
{
    private string $itemId;
    private Language $mainLanguage;
    private Title $title;
    private EventType $eventType;
    private Theme $theme;
    private LocationId $location;
    private Calendar $calendar;
    private ?DateTimeImmutable $publicationDate;

    public function __construct(
        string $itemId,
        Language $mainLanguage,
        Title $title,
        EventType $eventType,
        LocationId $location,
        Calendar $calendar,
        Theme $theme = null,
        DateTimeImmutable $publicationDate = null
    ) {
        $this->itemId = $itemId;
        $this->mainLanguage = $mainLanguage;
        $this->title = $title;
        $this->eventType = $eventType;
        $this->location = $location;
        $this->calendar = $calendar;
        $this->theme = $theme;
        $this->publicationDate = $publicationDate;
    }

    public function getItemId(): string
    {
        return $this->itemId;
    }

    public function getMainLanguage(): Language
    {
        return $this->mainLanguage;
    }

    public function getTitle(): Title
    {
        return $this->title;
    }

    public function getEventType(): EventType
    {
        return $this->eventType;
    }

    public function getTheme(): Theme
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

    public function getPublicationDate(DateTimeImmutable $default): ?DateTimeImmutable
    {
        if ($this->publicationDate && $this->publicationDate < $default) {
            return $default;
        }

        return $this->publicationDate;
    }
}
