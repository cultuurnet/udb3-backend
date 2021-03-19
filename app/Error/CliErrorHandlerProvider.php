<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Error;

use Silex\Application;
use Silex\ServiceProviderInterface;

class CliErrorHandlerProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app[ErrorLogger::class] = $app::share(
            function (Application $app): ErrorLogger {
                return new ErrorLogger(
                    LoggerFactory::create($app, new LoggerName('cli'))
                );
            }
        );
    }

    public function boot(Application $app): void
    {
    }
}
