<?php

namespace CultuurNet\UDB3\Silex\Console;

use CultuurNet\UDB3\Silex\Impersonator;
use Knp\Command\Command;

abstract class AbstractSystemUserCommand extends Command
{
    protected function impersonateUDB3SystemUser()
    {
        $app = $this->getSilexApplication();

        /** @var Impersonator $impersonator */
        $impersonator = $app['impersonator'];

        $impersonator->impersonate($app['udb3_system_user_metadata']);
    }
}
