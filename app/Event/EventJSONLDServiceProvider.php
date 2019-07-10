<?php

namespace CultuurNet\UDB3\Silex\Event;

use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use CultuurNet\UDB3\Cdb\PriceDescriptionParser;
use CultuurNet\UDB3\Doctrine\Event\ReadModel\CacheDocumentRepository;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\CdbXMLImporter;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\EventFactory;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\EventJsonDocumentLanguageAnalyzer;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\EventLDProjector;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\RelatedEventLDProjector;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CdbXMLItemBaseImporter;
use CultuurNet\UDB3\ReadModel\BroadcastingDocumentRepositoryDecorator;
use CultuurNet\UDB3\ReadModel\JsonDocumentLanguageEnricher;
use Silex\Application;
use Silex\ServiceProviderInterface;

class EventJSONLDServiceProvider implements ServiceProviderInterface
{
    public const PROJECTOR = 'event_jsonld_projector';
    public const RELATED_PROJECTOR = 'related_event_jsonld_projector';

    public function register(Application $app)
    {
        $app['event_jsonld_repository'] = $app->share(
            function ($app) {
                $cachedRepository =  new CacheDocumentRepository(
                    $app['event_jsonld_cache']
                );

                $broadcastingRepository = new BroadcastingDocumentRepositoryDecorator(
                    $cachedRepository,
                    $app['event_bus'],
                    new EventFactory(
                        $app['event_iri_generator']
                    )
                );

                return $broadcastingRepository;
            }
        );

        $app['event_jsonld_cache'] = $app->share(
            function (Application $app) {
                return $app['cache']('event_jsonld');
            }
        );

        $app[self::PROJECTOR] = $app->share(
            function ($app) {
                $projector = new EventLDProjector(
                    $app['event_jsonld_repository'],
                    $app['event_iri_generator'],
                    $app['place_service'],
                    $app['organizer_service'],
                    $app['media_object_serializer'],
                    $app['iri_offer_identifier_factory'],
                    $app['event_cdbxml_importer'],
                    new JsonDocumentLanguageEnricher(
                        new EventJsonDocumentLanguageAnalyzer()
                    ),
                    $app['config']['base_price_translations']
                );

                return $projector;
            }
        );

        $app[self::RELATED_PROJECTOR] = $app->share(
            function ($app) {
                $projector = new RelatedEventLDProjector(
                    $app['event_jsonld_repository'],
                    $app['event_service'],
                    $app['place_service'],
                    $app['organizer_service'],
                    $app['iri_offer_identifier_factory']
                );

                return $projector;
            }
        );

        $app['event_cdbxml_importer'] = $app->share(
            function (Application $app) {
                return new CdbXMLImporter(
                    new CdbXMLItemBaseImporter(
                        new PriceDescriptionParser(
                            new NumberFormatRepository(),
                            new CurrencyRepository()
                        ),
                        $app['config']['base_price_translations']
                    ),
                    $app['udb2_event_cdbid_extractor'],
                    $app['calendar_factory'],
                    $app['cdbxml_contact_info_importer']
                );
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
