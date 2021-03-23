<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Event;

use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use CultuurNet\UDB3\Cdb\PriceDescriptionParser;
use CultuurNet\UDB3\Doctrine\ReadModel\CacheDocumentRepository;
use CultuurNet\UDB3\Event\EventTypeResolver;
use CultuurNet\UDB3\Event\Productions\ProductionEnrichedEventRepository;
use CultuurNet\UDB3\Event\Productions\ProductionRepository;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\CdbXMLImporter;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\EventFactory;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\EventJsonDocumentLanguageAnalyzer;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\EventLDProjector;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\RelatedEventLDProjector;
use CultuurNet\UDB3\Offer\Popularity\PopularityEnrichedOfferRepository;
use CultuurNet\UDB3\Offer\Popularity\PopularityRepository;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CdbXMLItemBaseImporter;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\NewPropertyPolyfillOfferRepository;
use CultuurNet\UDB3\ReadModel\BroadcastingDocumentRepositoryDecorator;
use CultuurNet\UDB3\ReadModel\JsonDocumentLanguageEnricher;
use Silex\Application;
use Silex\ServiceProviderInterface;

class EventJSONLDServiceProvider implements ServiceProviderInterface
{
    public const PROJECTOR = 'event_jsonld_projector';
    public const RELATED_PROJECTOR = 'related_event_jsonld_projector';
    public const JSONLD_PROJECTED_EVENT_FACTORY = 'event_jsonld_projected_event_factory';

    public function register(Application $app)
    {
        $app['event_jsonld_repository'] = $app->share(
            function ($app) {
                return new BroadcastingDocumentRepositoryDecorator(
                    new NewPropertyPolyfillOfferRepository(
                        new PopularityEnrichedOfferRepository(
                            $app[PopularityRepository::class],
                            new ProductionEnrichedEventRepository(
                                new CacheDocumentRepository(
                                    $app['event_jsonld_cache']
                                ),
                                $app[ProductionRepository::class],
                                $app['event_iri_generator']
                            )
                        )
                    ),
                    $app['event_bus'],
                    new EventFactory(
                        $app['event_iri_generator']
                    )
                );
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
                    new EventTypeResolver(),
                    $app['config']['base_price_translations']
                );

                return $projector;
            }
        );

        $app[self::JSONLD_PROJECTED_EVENT_FACTORY] = $app->share(
            function ($app) {
                return new EventFactory(
                    $app['event_iri_generator']
                );
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
