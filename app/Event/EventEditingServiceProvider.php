<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Event;

use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\Broadway\EventHandling\ReplayFilteringEventListener;
use CultuurNet\UDB3\Event\Commands\EventCommandFactory;
use CultuurNet\UDB3\Event\EventEditingService;
use CultuurNet\UDB3\Event\EventOrganizerRelationService;
use CultuurNet\UDB3\Event\LocationMarkedAsDuplicateProcessManager;
use CultuurNet\UDB3\Event\RelocateEventToCanonicalPlace;
use CultuurNet\UDB3\Place\CanonicalPlaceRepository;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Silex\Search\Sapi3SearchServiceProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;

class EventEditingServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $app['event_editor'] = $app->share(
            function ($app) {
                return new EventEditingService(
                    $app['event_service'],
                    $app['event_command_bus'],
                    new Version4Generator(),
                    $app['event_jsonld_repository'],
                    new EventCommandFactory(),
                    $app['event_repository'],
                    $app['place_repository']
                );
            }
        );

        $app['event_organizer_relation_service'] = $app->share(
            function ($app) {
                return new EventOrganizerRelationService(
                    $app['event_editor'],
                    $app['event_relations_repository']
                );
            }
        );

        $app[LocationMarkedAsDuplicateProcessManager::class] = $app->share(
            function ($app) {
                return new ReplayFilteringEventListener(
                    new LocationMarkedAsDuplicateProcessManager(
                        new ResultsGenerator($app[Sapi3SearchServiceProvider::SEARCH_SERVICE_EVENTS]),
                        $app['event_command_bus']
                    )
                );
            }
        );

        $app[RelocateEventToCanonicalPlace::class] = $app->share(
            function ($app) {
                return new ReplayFilteringEventListener(
                    new RelocateEventToCanonicalPlace(
                        $app['event_command_bus'],
                        new CanonicalPlaceRepository($app['place_repository'])
                    )
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
