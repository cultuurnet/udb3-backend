<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\EventSourcing\ConvertsToGranularEvents;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Taxonomy\Category\CategoryDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Taxonomy\Category\CategoryNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;
use CultuurNet\UDB3\Theme;

final class MajorInfoUpdated extends AbstractEvent implements ConvertsToGranularEvents
{
    private string $title;
    private Category $eventType;
    private ?Theme $theme;
    private LocationId $location;
    private Calendar $calendar;

    public function __construct(
        string $eventId,
        string $title,
        Category $eventType,
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

    public function getEventType(): Category
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
                    new TypeUpdated($this->itemId, $this->eventType),
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
            'event_type' => (new CategoryNormalizer())->normalize($this->getEventType()),
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
            (new CategoryDenormalizer(CategoryDomain::eventType()))->denormalize($data['event_type'], Category::class),
            new LocationId($data['location']),
            Calendar::deserialize($data['calendar']),
            empty($data['theme']) ? null : Theme::deserialize($data['theme'])
        );
    }
}
