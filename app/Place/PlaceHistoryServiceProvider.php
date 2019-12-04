<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Place;

use CultuurNet\UDB3\Doctrine\ReadModel\CacheDocumentRepository;
use CultuurNet\UDB3\Place\ReadModel\History\HistoryProjector;
use Silex\Application;
use Silex\ServiceProviderInterface;

final class PlaceHistoryServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app['place_history_projector'] = $app->share(
            function ($app) {
                $projector = new HistoryProjector(
                    $app['places_history_repository']
                );
                return $projector;
            }
        );

        $app['places_history_repository'] = $app->share(
            function ($app) {
                return new CacheDocumentRepository(
                    $app['cache']('place_history')
                );
            }
        );
    }

    public function boot(Application $app): void
    {
    }
}
