<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Event\EventType;

final class UpdateType extends AbstractCommand
{
    protected string $typeId;

    /**
     * @param string $itemId
     */
    public function __construct($itemId, EventType $type)
    {
        parent::__construct($itemId);
        $this->typeId = $type->getId();
    }

    public function getTypeId(): string
    {
        return $this->typeId;
    }
}
