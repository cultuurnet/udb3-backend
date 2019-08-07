<?php

namespace CultuurNet\UDB3\Silex\Event;

use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\Event\Commands\EventCommandFactory;
use CultuurNet\UDB3\Event\EventEditingService;
use CultuurNet\UDB3\Event\EventOrganizerRelationService;
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
                    $app['labels.constraint_aware_service']
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
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }
}
