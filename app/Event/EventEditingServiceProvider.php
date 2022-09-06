<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Event;

use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\Broadway\EventHandling\ReplayFilteringEventListener;
use CultuurNet\UDB3\Event\Commands\EventCommandFactory;
use CultuurNet\UDB3\Event\EventEditingService;
use CultuurNet\UDB3\Event\EventOrganizerRelationService;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
use CultuurNet\UDB3\Event\RelocateEventToCanonicalPlace;
use CultuurNet\UDB3\Place\CanonicalPlaceRepository;
use Silex\Application;
use Silex\ServiceProviderInterface;

class EventEditingServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $app[EventOrganizerRelationService::class] = $app->share(
            function ($app) {
                return new EventOrganizerRelationService(
                    $app['event_command_bus'],
                    $app[EventRelationsRepository::class]
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
