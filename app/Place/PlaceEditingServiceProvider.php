<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Place;

use CultuurNet\UDB3\Place\PlaceOrganizerRelationService;
use CultuurNet\UDB3\Place\ReadModel\Relations\PlaceRelationsRepository;
use Silex\Application;
use Silex\ServiceProviderInterface;

class PlaceEditingServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
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

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }
}
