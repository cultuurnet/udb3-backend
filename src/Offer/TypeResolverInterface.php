<?php

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Event\EventType;
use ValueObjects\StringLiteral\StringLiteral;

interface TypeResolverInterface
{
    public function byId(StringLiteral $typeId): EventType;
}
