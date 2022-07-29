<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Event;

use CultuurNet\UDB3\Doctrine\ReadModel\CacheDocumentRepository;
use CultuurNet\UDB3\Event\ReadModel\OSLO\EventOSLOProjector;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
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

        $app[EventOSLOProjector::class] = $app::share(
            function ($app) {
                return new EventOSLOProjector(
                    $app['event_oslo_repository'],
                    new CallableIriGenerator(
                        function (string $eventId) use ($app) {
                            return $app['config']['url'] . '/event/' . $eventId . '/oslo';
                        }
                    )
                );
            }
        );
    }

    public function boot(Application $app): void
    {
    }
}
