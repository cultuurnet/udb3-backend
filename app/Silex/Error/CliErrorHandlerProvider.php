<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Error;

use CultuurNet\UDB3\Error\ErrorLogger;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Silex\Container\HybridContainerApplication;
use Silex\Application;
use Silex\ServiceProviderInterface;

class CliErrorHandlerProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app[ErrorLogger::class] = $app::share(
            function (HybridContainerApplication $app): ErrorLogger {
                return new ErrorLogger(
                    LoggerFactory::create($app->getLeagueContainer(), LoggerName::forCli())
                );
            }
        );
    }

    public function boot(Application $app): void
    {
    }
}
