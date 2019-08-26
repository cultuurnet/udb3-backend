<?php

namespace CultuurNet\UDB3\Silex\Place;

use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\Event\LocationMarkedAsDuplicateProcessManager;
use CultuurNet\UDB3\Offer\OfferEditingServiceWithLabelMemory;
use CultuurNet\UDB3\Place\Commands\PlaceCommandFactory;
use CultuurNet\UDB3\Place\DefaultPlaceEditingService;
use CultuurNet\UDB3\Place\PlaceOrganizerRelationService;
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
                return new DefaultPlaceEditingService(
                    $app['event_command_bus'],
                    new Version4Generator(),
                    $app['place_jsonld_repository'],
                    new PlaceCommandFactory(),
                    $app['place_repository'],
                    $app['labels.constraint_aware_service']
                );
            }
        );

        $app['place_organizer_relation_service'] = $app->share(
            function ($app) {
                return new PlaceOrganizerRelationService(
                    $app['place_editing_service'],
                    $app['place_relations_repository']
                );
            }
        );

        $app[LocationMarkedAsDuplicateProcessManager::class] = $app->share(
            function ($app) {
                return new LocationMarkedAsDuplicateProcessManager(
                    $app['event_relations_repository'],
                    $app['event_command_bus']
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
