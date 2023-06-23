<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

use InvalidArgumentException;

final class Production
{
    private ProductionId $productionId;

    private string $name;

    /**
     * @var string[]
     */
    private array $events;

    public function __construct(
        ProductionId $productionId,
        string $name,
        array $events
    ) {
        if (empty(trim($name))) {
            throw new InvalidArgumentException('Production name cannot be empty');
        }

        $this->productionId = $productionId;
        $this->name = trim($name);
        $this->events = $events;
    }

    public static function createEmpty(string $name): self
    {
        return new self(
            ProductionId::generate(),
            $name,
            []
        );
    }

    public function getProductionId(): ProductionId
    {
        return $this->productionId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function addEvents(array $events): self
    {
        $clone = clone($this);
        $clone->events = array_unique(array_merge($this->events, $events));

        return $clone;
    }

    public function addEvent(string $eventId): self
    {
        return $this->addEvents([$eventId]);
    }

    /**
     * string[]
     */
    public function getEventIds(): array
    {
        return $this->events;
    }

    public function containsEvent(string $eventId): bool
    {
        return array_search($eventId, $this->events) !== false;
    }
}
