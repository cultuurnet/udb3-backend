<?php

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Event\EventType;

abstract class AbstractUpdateType extends AbstractCommand
{
    /**
     * @var EventType
     */
    protected $type;

    /**
     * @param string $itemId
     * @param EventType $type
     */
    public function __construct($itemId, EventType $type)
    {
        parent::__construct($itemId);
        $this->type = $type;
    }

    /**
     * @return EventType
     */
    public function getType()
    {
        return $this->type;
    }
}
