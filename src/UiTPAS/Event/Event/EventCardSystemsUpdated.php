<?php

namespace CultuurNet\UDB3\UiTPAS\Event\Event;

use CultuurNet\UDB3\UiTPAS\CardSystem\CardSystems;
use CultuurNet\UDB3\UiTPAS\ValueObject\Id;

class EventCardSystemsUpdated
{
    /**
     * @var Id
     */
    private $id;

    /**
     * @var CardSystems
     */
    private $cardSystems;

    /**
     * @param Id $id
     * @param CardSystems $cardSystems
     */
    public function __construct(
        Id $id,
        CardSystems $cardSystems
    ) {
        $this->id = $id;
        $this->cardSystems = $cardSystems;
    }

    /**
     * @return Id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return CardSystems
     */
    public function getCardSystems()
    {
        return $this->cardSystems;
    }
}
