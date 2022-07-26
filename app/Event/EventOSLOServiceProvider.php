<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Event;

use CultuurNet\UDB3\Doctrine\ReadModel\CacheDocumentRepository;
use Silex\Application;
use Silex\ServiceProviderInterface;

final class EventOSLOServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app['event_oslo_repository'] = $app::share(
            function ($app) {
                return new CacheDocumentRepository($app['cache']('event_oslo'));
            }
        );
    }

    public function boot(Application $app): void
    {
    }
}
