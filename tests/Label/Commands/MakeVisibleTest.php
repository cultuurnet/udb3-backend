<?php

namespace CultuurNet\UDB3\Label\Commands;

use ValueObjects\Identity\UUID;

class MakeVisibleTest extends AbstractExtendsTest
{
    /**
     * @inheritdoc
     */
    public function createCommand(UUID $uuid)
    {
        return new MakeVisible($uuid);
    }
}
