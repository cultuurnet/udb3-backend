<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Place;

use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\Offer\DefaultOfferEditingService;
use CultuurNet\UDB3\Place\Commands\PlaceCommandFactory;
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
        $app['place_editing_service'] = $app->share(
            function ($app) {
                return new DefaultOfferEditingService(
                    $app['event_command_bus'],
                    new Version4Generator(),
                    $app['place_jsonld_repository'],
                    new PlaceCommandFactory()
                );
            }
        );

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
