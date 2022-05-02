<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Event;

use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use CultuurNet\UDB3\Cdb\CdbXMLToJsonLDLabelImporter;
use CultuurNet\UDB3\Cdb\PriceDescriptionParser;
use CultuurNet\UDB3\Curators\NewsArticleRepository;
use CultuurNet\UDB3\Doctrine\ReadModel\CacheDocumentRepository;
use CultuurNet\UDB3\Event\EventTypeResolver;
use CultuurNet\UDB3\Event\Productions\ProductionEnrichedEventRepository;
use CultuurNet\UDB3\Event\Productions\ProductionRepository;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\CdbXMLImporter;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\EventFactory;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\EventJsonDocumentLanguageAnalyzer;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\EventLDProjector;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\RelatedEventLDProjector;
use CultuurNet\UDB3\Event\Recommendations\DBALRecommendationsRepository;
use CultuurNet\UDB3\Event\Recommendations\RecommendationForEnrichedOfferRepository;
use CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject\VideoNormalizer;
use CultuurNet\UDB3\Offer\Popularity\PopularityEnrichedOfferRepository;
use CultuurNet\UDB3\Offer\Popularity\PopularityRepository;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CdbXMLItemBaseImporter;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CuratorEnrichedOfferRepository;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\MediaUrlOfferRepositoryDecorator;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\PropertyPolyfillOfferRepository;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\TermLabelOfferRepositoryDecorator;
use CultuurNet\UDB3\Offer\ReadModel\Metadata\OfferMetadataEnrichedOfferRepository;
use CultuurNet\UDB3\Offer\ReadModel\Metadata\OfferMetadataRepository;
use CultuurNet\UDB3\ReadModel\BroadcastingDocumentRepositoryDecorator;
use CultuurNet\UDB3\ReadModel\JsonDocumentLanguageEnricher;
use CultuurNet\UDB3\Silex\Error\LoggerFactory;
use CultuurNet\UDB3\Silex\Error\LoggerName;
use CultuurNet\UDB3\Term\TermRepository;
use Silex\Application;
use Silex\ServiceProviderInterface;

class EventJSONLDServiceProvider implements ServiceProviderInterface
{
    public const PROJECTOR = 'event_jsonld_projector';
    public const RELATED_PROJECTOR = 'related_event_jsonld_projector';
    public const JSONLD_PROJECTED_EVENT_FACTORY = 'event_jsonld_projected_event_factory';

    public function register(Application $app): void
    {
        $app['event_jsonld_repository'] = $app->share(
            function ($app) {
                $repository = new CacheDocumentRepository(
                    $app['event_jsonld_cache']
                );

                $repository = new ProductionEnrichedEventRepository(
                    $repository,
                    $app[ProductionRepository::class],
                    $app['event_iri_generator']
                );

                $repository = new OfferMetadataEnrichedOfferRepository(
                    $app[OfferMetadataRepository::class],
                    $repository
                );

                $repository = new PopularityEnrichedOfferRepository(
                    $app[PopularityRepository::class],
                    $repository
                );

                $repository = new RecommendationForEnrichedOfferRepository(
                    new DBALRecommendationsRepository($app['dbal_connection']),
                    $app['event_iri_generator'],
                    $repository
                );

                $repository = new PropertyPolyfillOfferRepository($repository);

                $repository = new TermLabelOfferRepositoryDecorator($repository, $app[TermRepository::class]);

                $repository = new MediaUrlOfferRepositoryDecorator($repository, $app['media_url_mapping']);

                $repository = new CuratorEnrichedOfferRepository(
                    $repository,
                    $app[NewsArticleRepository::class],
                    LoggerFactory::create($app, LoggerName::forConfig()),
                    $app['config']['curator_labels']
                );

                return new BroadcastingDocumentRepositoryDecorator(
                    $repository,
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
                return new EventLDProjector(
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
                    $app['config']['base_price_translations'],
                    new VideoNormalizer($app['config']['media']['video_default_copyright'])
                );
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
                return new RelatedEventLDProjector(
                    $app['event_jsonld_repository'],
                    $app['event_relations_repository'],
                    $app['place_service'],
                    $app['organizer_service'],
                    $app['iri_offer_identifier_factory']
                );
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
                    $app['cdbxml_contact_info_importer'],
                    $app[CdbXMLToJsonLDLabelImporter::class]
                );
            }
        );
    }

    public function boot(Application $app): void
    {
    }
}
