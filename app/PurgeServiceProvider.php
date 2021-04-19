<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\Silex\Console\PurgeModelCommand;
use Silex\Application;
use Silex\ServiceProviderInterface;

class PurgeServiceProvider implements ServiceProviderInterface
{
    public function register(Application $application)
    {
        $application[PurgeModelCommand::class] = $application->share(
            function (Application $application) {
                return new PurgeModelCommand($application['dbal_connection']);
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function boot(Application $app)
    {
    }
}
