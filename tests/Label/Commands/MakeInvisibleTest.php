<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Commands;

use ValueObjects\Identity\UUID;

class MakeInvisibleTest extends AbstractExtendsTest
{
    /**
     * @inheritdoc
     */
    public function createCommand(UUID $uuid)
    {
        return new MakeInvisible($uuid);
    }
}
