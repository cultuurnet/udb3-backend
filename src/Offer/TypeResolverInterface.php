<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Event\EventType;

interface TypeResolverInterface
{
    public function byId(string $typeId): EventType;
}
