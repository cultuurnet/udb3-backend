<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\Event\Event;

use CultuurNet\UDB3\UiTPAS\CardSystem\CardSystem;
use CultuurNet\UDB3\UiTPAS\ValueObject\Id;

class EventCardSystemsUpdated
{
    private Id $id;

    /**
     * @var CardSystem[]
     */
    private array $cardSystems;

    /**
     * @param CardSystem[] $cardSystems
     */
    public function __construct(
        Id $id,
        array $cardSystems
    ) {
        $this->id = $id;
        $this->cardSystems = $cardSystems;
    }

    public function getId(): Id
    {
        return $this->id;
    }

    /**
     * @return CardSystem[]
     */
    public function getCardSystems(): array
    {
        return $this->cardSystems;
    }
}
