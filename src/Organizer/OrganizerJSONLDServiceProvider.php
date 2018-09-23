<?php

namespace CultuurNet\UDB3\Silex\Organizer;

use CultuurNet\UDB3\Organizer\ReadModel\JSONLD\EventFactory;
use CultuurNet\UDB3\Organizer\ReadModel\JSONLD\OrganizerJsonDocumentLanguageAnalyzer;
use CultuurNet\UDB3\ReadModel\BroadcastingDocumentRepositoryDecorator;
use CultuurNet\UDB3\ReadModel\JsonDocumentLanguageEnricher;
use Silex\Application;
use Silex\ServiceProviderInterface;

class OrganizerJSONLDServiceProvider implements ServiceProviderInterface
{
    public const PROJECTOR = 'organizer_jsonld_projector';

    public function register(Application $app)
    {
        $app[self::PROJECTOR] = $app->share(
            function ($app) {
                return new \CultuurNet\UDB3\Organizer\OrganizerLDProjector(
                    $app['organizer_jsonld_repository'],
                    $app['organizer_iri_generator'],
                    $app['event_bus'],
                    new JsonDocumentLanguageEnricher(
                        new OrganizerJsonDocumentLanguageAnalyzer()
                    )
                );
            }
        );

        $app['real_organizer_jsonld_repository'] = $app->share(
            function ($app) {
                return new \CultuurNet\UDB3\Doctrine\Event\ReadModel\CacheDocumentRepository(
                    $app['organizer_jsonld_cache']
                );
            }
        );

        $app['organizer_jsonld_repository'] = $app->share(
            function ($app) {
                return new BroadcastingDocumentRepositoryDecorator(
                    $app['real_organizer_jsonld_repository'],
                    $app['event_bus'],
                    new EventFactory(
                        $app['organizer_iri_generator']
                    )
                );
            }
        );

        $app['organizer_jsonld_cache'] = $app->share(
            function ($app) {
                return $app['cache']('organizer_jsonld');
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
