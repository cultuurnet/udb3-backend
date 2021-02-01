<?php

namespace CultuurNet\UDB3\EventSourcing;

interface AggregateCopiedEventInterface
{
    /**
     * @return string
     */
    public function getParentAggregateId();
}
