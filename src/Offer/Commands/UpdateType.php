<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

final class UpdateType extends AbstractCommand
{
    protected string $typeId;

    public function __construct(string $itemId, string $typeId)
    {
        parent::__construct($itemId);
        $this->typeId = $typeId;
    }

    public function getTypeId(): string
    {
        return $this->typeId;
    }
}
