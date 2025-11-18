<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\Event\Place;

use CultuurNet\UDB3\UiTPAS\CardSystem\CardSystem;
use CultuurNet\UDB3\UiTPAS\ValueObject\Id;

class PlaceCardSystemsUpdated
{
    /**
     * @param CardSystem[] $cardSystems
     */
    public function __construct(
        private readonly Id $id,
        private readonly array $cardSystems
    ) {
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
