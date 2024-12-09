<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\EventSourcing\ConvertsToGranularEvents;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\CalendarSerializer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Taxonomy\Category\CategoryDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Taxonomy\Category\CategoryNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Place\PlaceEvent;

final class MajorInfoUpdated extends PlaceEvent implements ConvertsToGranularEvents
{
    private string $title;
    private Category $eventType;
    private Address $address;
    private Calendar $calendar;

    final public function __construct(
        string $placeId,
        string $title,
        Category $eventType,
        Address $address,
        Calendar $calendar
    ) {
        parent::__construct($placeId);

        $this->title = $title;
        $this->eventType = $eventType;
        $this->address = $address;
        $this->calendar = $calendar;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getEventType(): Category
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

    public function toGranularEvents(): array
    {
        return array_values(
            array_filter(
                [
                    new TitleUpdated($this->placeId, $this->title),
                    new TypeUpdated($this->placeId, $this->eventType),
                    new AddressUpdated($this->placeId, $this->address),
                    new CalendarUpdated($this->placeId, $this->calendar),
                ]
            )
        );
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'title' => $this->getTitle(),
            'event_type' => (new CategoryNormalizer())->normalize($this->getEventType()),
            'address' => $this->getAddress()->serialize(),
            'calendar' => (new CalendarSerializer($this->getCalendar()))->serialize(),
        ];
    }

    public static function deserialize(array $data): MajorInfoUpdated
    {
        return new static(
            $data['place_id'],
            $data['title'],
            (new CategoryDenormalizer(CategoryDomain::eventType()))->denormalize($data['event_type'], Category::class),
            Address::deserialize($data['address']),
            CalendarSerializer::deserialize($data['calendar'])
        );
    }
}
