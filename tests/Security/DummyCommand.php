<?php

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;

class DummyCommand implements AuthorizableCommandInterface
{
    public function getItemId()
    {
        return null;
    }

    public function getPermission()
    {
        return null;
    }
}
