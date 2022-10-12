<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use Broadway\EventHandling\EventBus;
use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use CultuurNet\UDB3\Cdb\CdbXmlPriceInfoParser;
use CultuurNet\UDB3\Cdb\CdbXMLToJsonLDLabelImporter;
use CultuurNet\UDB3\Cdb\PriceDescriptionParser;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Doctrine\ReadModel\CacheDocumentRepository;
use CultuurNet\UDB3\Model\Serializer\Place\NilLocationNormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject\VideoNormalizer;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Offer\Popularity\PopularityEnrichedOfferRepository;
use CultuurNet\UDB3\Offer\Popularity\PopularityRepository;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CdbXmlContactInfoImporter;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CdbXMLItemBaseImporter;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\EmbeddingRelatedResourcesOfferRepository;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\MediaUrlOfferRepositoryDecorator;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\PropertyPolyfillOfferRepository;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\TermLabelOfferRepositoryDecorator;
use CultuurNet\UDB3\Offer\ReadModel\Metadata\OfferMetadataEnrichedOfferRepository;
use CultuurNet\UDB3\Offer\ReadModel\Metadata\OfferMetadataRepository;
use CultuurNet\UDB3\Place\Canonical\DuplicatePlacesEnrichedPlaceRepository;
use CultuurNet\UDB3\Place\ReadModel\JSONLD\CdbXMLImporter;
use CultuurNet\UDB3\Place\ReadModel\JSONLD\EventFactory;
use CultuurNet\UDB3\Place\ReadModel\JSONLD\PlaceJsonDocumentLanguageAnalyzer;
use CultuurNet\UDB3\Place\ReadModel\JSONLD\PlaceLDProjector;
use CultuurNet\UDB3\ReadModel\BroadcastingDocumentRepositoryDecorator;
use CultuurNet\UDB3\ReadModel\JsonDocumentLanguageEnricher;
use CultuurNet\UDB3\Labels\LabelServiceProvider;
use CultuurNet\UDB3\Term\TermRepository;

final class PlaceJSONLDServiceProvider extends AbstractServiceProvider
{
    public const PROJECTOR = 'place_jsonld_projector';
    public const JSONLD_PROJECTED_EVENT_FACTORY = 'place_jsonld_projected_event_factory';

    protected function getProvidedServiceNames(): array
    {
        return [
            self::PROJECTOR,
            self::JSONLD_PROJECTED_EVENT_FACTORY,
            'place_jsonld_repository',
            'place_jsonld_cache',
            'place_cdbxml_importer',
        ];
    }
    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            self::PROJECTOR,
            function () use ($container) {
                return new PlaceLDProjector(
                    $container->get('place_jsonld_repository'),
                    $container->get('place_iri_generator'),
                    $container->get('organizer_service'),
                    $container->get('media_object_serializer'),
                    $container->get('place_cdbxml_importer'),
                    new JsonDocumentLanguageEnricher(
                        new PlaceJsonDocumentLanguageAnalyzer()
                    ),
                    $container->get('config')['base_price_translations'],
                    new VideoNormalizer($container->get('config')['media']['video_default_copyright'])
                );
            }
        );

        $container->addShared(
            self::JSONLD_PROJECTED_EVENT_FACTORY,
            function () use ($container) {
                return new EventFactory(
                    $container->get('place_iri_generator')
                );
            }
        );

        $container->addShared(
            'place_jsonld_repository',
            function () use ($container) {
                $dummyPlaceIds = [];
                if (isset($container->get('config')['bookable_event']['dummy_place_ids'])) {
                    $dummyPlaceIds = $container->get('config')['bookable_event']['dummy_place_ids'];
                }
                $repository = new DummyPlaceProjectionEnricher(
                    new CacheDocumentRepository(
                        $container->get('place_jsonld_cache')
                    ),
                    $dummyPlaceIds
                );

                $repository = EmbeddingRelatedResourcesOfferRepository::createForPlaceRepository(
                    $repository,
                    $container->get('organizer_jsonld_repository')
                );

                $repository = new NilLocationEnrichedPlaceRepository(
                    new NilLocationNormalizer($container->get('place_iri_generator')),
                    $repository
                );

                $repository = new OfferMetadataEnrichedOfferRepository(
                    $container->get(OfferMetadataRepository::class),
                    $repository
                );

                $repository = new PopularityEnrichedOfferRepository(
                    $container->get(PopularityRepository::class),
                    $repository
                );

                if (isset($container->get('config')['polyfill_duplicate_places']) && $container->get('config')['polyfill_duplicate_places']) {
                    $repository = new DuplicatePlacesEnrichedPlaceRepository(
                        $container->get('duplicate_place_repository'),
                        $container->get('place_iri_generator'),
                        $repository
                    );
                }

                $repository = new PropertyPolyfillOfferRepository(
                    $repository,
                    $container->get(LabelServiceProvider::JSON_READ_REPOSITORY),
                    OfferType::place()
                );

                $repository = new TermLabelOfferRepositoryDecorator($repository, $container->get(TermRepository::class));

                $repository = new MediaUrlOfferRepositoryDecorator($repository, $container->get('media_url_mapping'));

                return new BroadcastingDocumentRepositoryDecorator(
                    $repository,
                    $container->get(EventBus::class),
                    $container->get(self::JSONLD_PROJECTED_EVENT_FACTORY)
                );
            }
        );

        $container->addShared(
            'place_jsonld_cache',
            function () use ($container) {
                return $container->get('cache')('place_jsonld');
            }
        );

        $container->addShared(
            'place_cdbxml_importer',
            function () use ($container) {
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
                    $container->get('calendar_factory'),
                    $container->get(CdbXmlContactInfoImporter::class),
                    $container->get(CdbXMLToJsonLDLabelImporter::class)
                );
            }
        );
    }
}
