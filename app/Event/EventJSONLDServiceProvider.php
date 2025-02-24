<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use Broadway\EventHandling\EventBus;
use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use CultuurNet\UDB3\Calendar\CalendarFactory;
use CultuurNet\UDB3\Cdb\CdbXmlPriceInfoParser;
use CultuurNet\UDB3\Cdb\CdbXMLToJsonLDLabelImporter;
use CultuurNet\UDB3\Cdb\PriceDescriptionParser;
use CultuurNet\UDB3\Completeness\CompletenessFromWeights;
use CultuurNet\UDB3\Completeness\Weights;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Contributor\ContributorEnrichedRepository;
use CultuurNet\UDB3\Contributor\ContributorRepository;
use CultuurNet\UDB3\Curators\NewsArticleRepository;
use CultuurNet\UDB3\Doctrine\ReadModel\CacheDocumentRepository;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Event\Productions\ProductionEnrichedEventRepository;
use CultuurNet\UDB3\Event\Productions\ProductionRepository;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\CdbXMLImporter;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\EventFactory;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\EventJsonDocumentLanguageAnalyzer;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\EventLDProjector;
use CultuurNet\UDB3\Event\Recommendations\DBALRecommendationsRepository;
use CultuurNet\UDB3\Event\Recommendations\RecommendationForEnrichedOfferRepository;
use CultuurNet\UDB3\Labels\LabelServiceProvider;
use CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject\VideoNormalizer;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Offer\Popularity\PopularityEnrichedOfferRepository;
use CultuurNet\UDB3\Offer\Popularity\PopularityRepository;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CdbXmlContactInfoImporter;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CdbXMLItemBaseImporter;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CuratorEnrichedOfferRepository;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\EmbeddingRelatedResourcesOfferRepository;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\MediaUrlOfferRepositoryDecorator;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\PropertyPolyfillOfferRepository;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\TermLabelOfferRepositoryDecorator;
use CultuurNet\UDB3\Offer\ReadModel\Metadata\OfferMetadataEnrichedOfferRepository;
use CultuurNet\UDB3\Offer\ReadModel\Metadata\OfferMetadataRepository;
use CultuurNet\UDB3\ReadModel\BroadcastingDocumentRepositoryDecorator;
use CultuurNet\UDB3\ReadModel\JsonDocumentLanguageEnricher;
use CultuurNet\UDB3\Term\TermRepository;
use CultuurNet\UDB3\User\CurrentUser;

final class EventJSONLDServiceProvider extends AbstractServiceProvider
{
    public const PROJECTOR = 'event_jsonld_projector';
    public const JSONLD_PROJECTED_EVENT_FACTORY = 'event_jsonld_projected_event_factory';

    protected function getProvidedServiceNames(): array
    {
        return [
            'event_jsonld_repository',
            'event_jsonld_cache',
            self::PROJECTOR,
            self::JSONLD_PROJECTED_EVENT_FACTORY,
            'event_cdbxml_importer',
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'event_jsonld_repository',
            function () use ($container): BroadcastingDocumentRepositoryDecorator {
                $repository = $container->get('event_jsonld_cache');

                $repository = EmbeddingRelatedResourcesOfferRepository::createForEventRepository(
                    $repository,
                    $container->get('place_jsonld_repository'),
                    $container->get('organizer_jsonld_repository'),
                );

                $repository = new ProductionEnrichedEventRepository(
                    $repository,
                    $container->get(ProductionRepository::class),
                    $container->get('event_iri_generator'),
                );

                $repository = new OfferMetadataEnrichedOfferRepository(
                    $container->get(OfferMetadataRepository::class),
                    $repository,
                );

                $repository = new PopularityEnrichedOfferRepository(
                    $container->get(PopularityRepository::class),
                    $repository,
                );

                $repository = new ContributorEnrichedRepository(
                    $container->get(ContributorRepository::class),
                    $repository,
                    $container->get('offer_permission_voter'),
                    $container->get(CurrentUser::class)->getId()
                );

                $repository = new RecommendationForEnrichedOfferRepository(
                    new DBALRecommendationsRepository($container->get('dbal_connection')),
                    $container->get('event_iri_generator'),
                    $repository,
                );

                $repository = new PropertyPolyfillOfferRepository(
                    $repository,
                    $container->get(LabelServiceProvider::JSON_READ_REPOSITORY),
                    OfferType::event(),
                );

                $repository = new TermLabelOfferRepositoryDecorator(
                    $repository,
                    $container->get(TermRepository::class),
                );

                $repository = new MediaUrlOfferRepositoryDecorator(
                    $repository,
                    $container->get('media_url_mapping'),
                );

                $repository = new CuratorEnrichedOfferRepository(
                    $repository,
                    $container->get(NewsArticleRepository::class),
                    LoggerFactory::create($container, LoggerName::forConfig()),
                    $container->get('config')['curator_labels'],
                );

                return new BroadcastingDocumentRepositoryDecorator(
                    $repository,
                    $container->get(EventBus::class),
                    new EventFactory($container->get('event_iri_generator')),
                );
            }
        );

        $container->addShared(
            'event_jsonld_cache',
            function () use ($container) {
                $repository = new CacheDocumentRepository(
                    $container->get('persistent_cache')('event_jsonld'),
                    $container->get('config')['cache']['allowed_retries'] ?? 3,
                    $container->get('config')['cache']['milliseconds_between_retry'] ?? 0
                );

                $repository->setLogger(LoggerFactory::create($container, LoggerName::forWeb()));

                return $repository;
            }
        );

        $container->addShared(
            self::PROJECTOR,
            function () use ($container): EventLDProjector {
                $eventLDProjector = new EventLDProjector(
                    new BroadcastingDocumentRepositoryDecorator(
                        $container->get('event_jsonld_cache'),
                        $container->get(EventBus::class),
                        new EventFactory($container->get('event_iri_generator')),
                    ),
                    $container->get('event_iri_generator'),
                    $container->get('place_iri_generator'),
                    $container->get('organizer_iri_generator'),
                    $container->get('place_service'),
                    $container->get('organizer_jsonld_repository'),
                    $container->get('media_object_serializer'),
                    $container->get('iri_offer_identifier_factory'),
                    $container->get('event_cdbxml_importer'),
                    new JsonDocumentLanguageEnricher(new EventJsonDocumentLanguageAnalyzer()),
                    new EventTypeResolver(),
                    $container->get('config')['base_price_translations'],
                    new VideoNormalizer($container->get('config')['media']['video_default_copyright']),
                    new CompletenessFromWeights(
                        Weights::fromConfig($container->get('config')['completeness']['event'])
                    )
                );

                $eventLDProjector->setLogger(LoggerFactory::create($container, LoggerName::forWeb()));
                $eventLDProjector->setNrOfRetries(
                    $container->get('config')['event_ld_projector']['nr_of_retries'] ?? 3
                );
                $eventLDProjector->setTimeBetweenRetries(
                    $container->get('config')['event_ld_projector']['time_between_retries'] ?? 500
                );

                return $eventLDProjector;
            }
        );

        $container->addShared(
            self::JSONLD_PROJECTED_EVENT_FACTORY,
            function () use ($container): EventFactory {
                return new EventFactory($container->get('event_iri_generator'));
            }
        );

        $container->addShared(
            'event_cdbxml_importer',
            function () use ($container): CdbXMLImporter {
                return new CdbXMLImporter(
                    new CdbXMLItemBaseImporter(
                        new CdbXmlPriceInfoParser(
                            new PriceDescriptionParser(
                                new NumberFormatRepository(),
                                new CurrencyRepository()
                            )
                        ),
                        $container->get('config')['base_price_translations']
                    ),
                    $container->get('udb2_event_cdbid_extractor'),
                    $container->get(CalendarFactory::class),
                    $container->get(CdbXmlContactInfoImporter::class),
                    $container->get(CdbXMLToJsonLDLabelImporter::class),
                );
            }
        );
    }
}
