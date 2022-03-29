<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Organizer;

use CultuurNet\UDB3\Doctrine\ReadModel\CacheDocumentRepository;
use CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject\ImageNormalizer;
use CultuurNet\UDB3\Organizer\OrganizerLDProjector;
use CultuurNet\UDB3\Organizer\ReadModel\JSONLD\EventFactory;
use CultuurNet\UDB3\Organizer\ReadModel\JSONLD\NewPropertyPolyfillOfferRepository;
use CultuurNet\UDB3\Organizer\ReadModel\JSONLD\OrganizerJsonDocumentLanguageAnalyzer;
use CultuurNet\UDB3\ReadModel\BroadcastingDocumentRepositoryDecorator;
use CultuurNet\UDB3\ReadModel\JsonDocumentLanguageEnricher;
use Silex\Application;
use Silex\ServiceProviderInterface;

class OrganizerJSONLDServiceProvider implements ServiceProviderInterface
{
    public const PROJECTOR = 'organizer_jsonld_projector';

    public const JSONLD_PROJECTED_EVENT_FACTORY = 'organizer_jsonld_projected_event_factory';

    public function register(Application $app): void
    {
        $app[self::PROJECTOR] = $app->share(
            function ($app) {
                return new OrganizerLDProjector(
                    $app['organizer_jsonld_repository'],
                    $app['organizer_iri_generator'],
                    new JsonDocumentLanguageEnricher(
                        new OrganizerJsonDocumentLanguageAnalyzer()
                    ),
                    new ImageNormalizer(
                        $app['media_object_repository'],
                        $app['media_object_iri_generator']
                    )
                );
            }
        );

        $app[self::JSONLD_PROJECTED_EVENT_FACTORY] = $app->share(
            function ($app) {
                return new EventFactory(
                    $app['organizer_iri_generator']
                );
            }
        );

        $app['organizer_jsonld_repository'] = $app->share(
            function ($app) {
                $repository = new CacheDocumentRepository($app['organizer_jsonld_cache']);
                $repository = new NewPropertyPolyfillOfferRepository($repository);

                return new BroadcastingDocumentRepositoryDecorator(
                    $repository,
                    $app['event_bus'],
                    $app[self::JSONLD_PROJECTED_EVENT_FACTORY]
                );
            }
        );

        $app['organizer_jsonld_cache'] = $app->share(
            function ($app) {
                return $app['cache']('organizer_jsonld');
            }
        );
    }

    public function boot(Application $app): void
    {
    }
}
