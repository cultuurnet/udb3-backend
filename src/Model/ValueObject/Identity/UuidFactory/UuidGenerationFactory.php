<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Identity\UuidFactory;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

class UuidGenerationFactory implements UuidFactoryInterface
{
    public function uuid4(): Uuid
    {
        return Uuid::uuid4();
    }
}
