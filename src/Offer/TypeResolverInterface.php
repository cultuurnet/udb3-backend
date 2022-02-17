<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\StringLiteral;

interface TypeResolverInterface
{
    public function byId(StringLiteral $typeId): EventType;
}
