<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Place;

use CultuurNet\UDB3\Place\PlaceOrganizerRelationService;
use CultuurNet\UDB3\Place\ReadModel\Relations\PlaceRelationsRepository;
use Silex\Application;
use Silex\ServiceProviderInterface;

class PlaceEditingServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app[PlaceOrganizerRelationService::class] = $app->share(
            function ($app) {
                return new PlaceOrganizerRelationService(
                    $app['event_command_bus'],
                    $app[PlaceRelationsRepository::class]
                );
            }
        );
    }

    public function boot(Application $app): void
    {
    }
}
