<?php

namespace CultuurNet\UDB3\Label\Commands;

use ValueObjects\Identity\UUID;

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
