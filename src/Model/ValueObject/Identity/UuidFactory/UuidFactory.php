<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Identity\UuidFactory;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

interface UuidFactory
{
    public function uuid4(): Uuid;
}
