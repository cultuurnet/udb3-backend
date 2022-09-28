<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Error;

use Silex\Application;
use Silex\ServiceProviderInterface;

class WebErrorHandlerProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app[ErrorLogger::class] = $app::share(
            function (Application $app): ErrorLogger {
                return new ErrorLogger(
                    LoggerFactory::create($app, LoggerName::forWeb())
                );
            }
        );

        $app[WebErrorHandler::class] = $app::share(
            function (Application $app): WebErrorHandler {
                return new WebErrorHandler(
                    $app[ErrorLogger::class],
                    $app['debug'] === true
                );
            }
        );
    }

    public function boot(Application $app): void
    {
    }
}
