<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Event\EventEvent;
use CultuurNet\UDB3\EventSourcing\ConvertsToGranularEvents;
use CultuurNet\UDB3\EventSourcing\MainLanguageDefined;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Taxonomy\Category\CategoryDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Taxonomy\Category\CategoryNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use DateTimeImmutable;
use DateTimeInterface;

final class EventCreated extends EventEvent implements ConvertsToGranularEvents, MainLanguageDefined
{
    private Language $mainLanguage;
    private string $title;
    private Category $eventType;
    private ?Category $theme;
    private LocationId $location;
    private Calendar $calendar;
    private ?DateTimeImmutable $publicationDate;

    public function __construct(
        string $eventId,
        Language $mainLanguage,
        string $title,
        Category $eventType,
        LocationId $location,
        Calendar $calendar,
        ?Category $theme = null,
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

    public function getEventType(): Category
    {
        return $this->eventType;
    }

    public function getTheme(): ?Category
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
            $theme = (new CategoryNormalizer())->normalize($this->getTheme());
        }
        $publicationDate = null;
        if (!is_null($this->getPublicationDate())) {
            $publicationDate = $this->getPublicationDate()->format(DateTimeInterface::ATOM);
        }
        return parent::serialize() + [
            'main_language' => $this->mainLanguage->getCode(),
            'title' => $this->getTitle(),
            'event_type' => (new CategoryNormalizer())->normalize($this->getEventType()),
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
            $theme = (new CategoryDenormalizer(CategoryDomain::theme()))->denormalize($data['theme'], Category::class);
        }
        $publicationDate = null;
        if (!empty($data['publication_date'])) {
            $publicationDate = DateTimeFactory::fromAtom($data['publication_date']);
        }
        return new self(
            $data['event_id'],
            new Language($data['main_language']),
            $data['title'],
            (new CategoryDenormalizer(CategoryDomain::eventType()))->denormalize($data['event_type'], Category::class),
            new LocationId($data['location']),
            Calendar::deserialize($data['calendar']),
            $theme,
            $publicationDate
        );
    }
}
