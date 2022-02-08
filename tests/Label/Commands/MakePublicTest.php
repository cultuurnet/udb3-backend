<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

class MakePublicTest extends AbstractExtendsTest
{
    /**
     * @inheritdoc
     */
    public function createCommand(UUID $uuid)
    {
        return new MakePublic($uuid);
    }
}
