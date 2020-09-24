<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex;

use Sentry\ClientBuilder;
use Sentry\State\Hub;
use Sentry\State\HubInterface;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Throwable;

class SentryServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app[HubInterface::class] = $app->share(
            function ($app) {
                return new Hub(
                    ClientBuilder::create([
                        'dsn' => $app['config']['sentry']['dsn'],
                        'environment' => $app['config']['sentry']['environment'],
                    ])->getClient()
                );
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
