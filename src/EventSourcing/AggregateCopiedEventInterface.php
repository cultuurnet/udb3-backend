<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventSourcing;

interface AggregateCopiedEventInterface
{
    public function getParentAggregateId(): string;
}
