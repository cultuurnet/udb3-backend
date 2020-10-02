<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex;

use Sentry\SentrySdk;
use Sentry\State\HubInterface;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Throwable;
use function Sentry\init;

class SentryServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
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

        $app['uncaught_error_handler'] = $app->share(
            function ($app) {
                /** @var HubInterface $sentryHub */
                $sentryHub = $app[HubInterface::class];

                return static function (Throwable $throwable) use ($sentryHub) {
                    $sentryHub->captureException($throwable);
                    throw $throwable;
                };
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
