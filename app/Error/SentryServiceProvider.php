<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Error;

use Sentry\SentrySdk;
use Sentry\State\HubInterface;
use Silex\Application;
use Silex\ServiceProviderInterface;
use function Sentry\init;

class SentryServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app[HubInterface::class] = $app->share(
            function (Application $app) {
                init([
                    'dsn' => $app['config']['sentry']['dsn'],
                    'environment' => $app['config']['sentry']['environment'],
                ]);

                return SentrySdk::getCurrentHub();
            }
        );
    }

    public function boot(Application $app): void
    {
    }
}
