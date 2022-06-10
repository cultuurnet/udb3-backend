<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Place;

use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use CultuurNet\UDB3\Cdb\CdbXMLToJsonLDLabelImporter;
use CultuurNet\UDB3\Cdb\PriceDescriptionParser;
use CultuurNet\UDB3\Doctrine\ReadModel\CacheDocumentRepository;
use CultuurNet\UDB3\Model\Serializer\Place\NilLocationNormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject\VideoNormalizer;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Offer\Popularity\PopularityEnrichedOfferRepository;
use CultuurNet\UDB3\Offer\Popularity\PopularityRepository;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CdbXMLItemBaseImporter;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\MediaUrlOfferRepositoryDecorator;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\PropertyPolyfillOfferRepository;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\TermLabelOfferRepositoryDecorator;
use CultuurNet\UDB3\Offer\ReadModel\Metadata\OfferMetadataEnrichedOfferRepository;
use CultuurNet\UDB3\Offer\ReadModel\Metadata\OfferMetadataRepository;
use CultuurNet\UDB3\Place\Canonical\DuplicatePlacesEnrichedPlaceRepository;
use CultuurNet\UDB3\Place\DummyPlaceProjectionEnricher;
use CultuurNet\UDB3\Place\NilLocationEnrichedPlaceRepository;
use CultuurNet\UDB3\Place\ReadModel\JSONLD\CdbXMLImporter;
use CultuurNet\UDB3\Place\ReadModel\JSONLD\EventFactory;
use CultuurNet\UDB3\Place\ReadModel\JSONLD\PlaceJsonDocumentLanguageAnalyzer;
use CultuurNet\UDB3\Place\ReadModel\JSONLD\PlaceLDProjector;
use CultuurNet\UDB3\Place\ReadModel\JSONLD\RelatedPlaceLDProjector;
use CultuurNet\UDB3\ReadModel\BroadcastingDocumentRepositoryDecorator;
use CultuurNet\UDB3\ReadModel\JsonDocumentLanguageEnricher;
use CultuurNet\UDB3\Silex\Labels\LabelServiceProvider;
use CultuurNet\UDB3\Term\TermRepository;
use Silex\Application;
use Silex\ServiceProviderInterface;

class PlaceJSONLDServiceProvider implements ServiceProviderInterface
{
    public const PROJECTOR = 'place_jsonld_projector';
    public const RELATED_PROJECTOR = 'related_place_jsonld_projector';

    public const JSONLD_REPOSITORY = 'place_jsonld_repository';
    public const JSONLD_PROJECTED_EVENT_FACTORY = 'place_jsonld_projected_event_factory';

    public function register(Application $app)
    {
        $app[self::PROJECTOR] = $app->share(
            function ($app) {
                $projector = new PlaceLDProjector(
                    $app[self::JSONLD_REPOSITORY],
                    $app['place_iri_generator'],
                    $app['organizer_service'],
                    $app['media_object_serializer'],
                    $app['place_cdbxml_importer'],
                    new JsonDocumentLanguageEnricher(
                        new PlaceJsonDocumentLanguageAnalyzer()
                    ),
                    $app['config']['base_price_translations'],
                    new VideoNormalizer($app['config']['media']['video_default_copyright'])
                );

                return $projector;
            }
        );

        $app[self::RELATED_PROJECTOR] = $app->share(
            function ($app) {
                $projector = new RelatedPlaceLDProjector(
                    $app[self::JSONLD_REPOSITORY],
                    $app['organizer_service'],
                    $app['place_relations_repository']
                );

                return $projector;
            }
        );

        $app[self::JSONLD_PROJECTED_EVENT_FACTORY] = $app->share(
            function ($app) {
                return new EventFactory(
                    $app['place_iri_generator']
                );
            }
        );

        $app[self::JSONLD_REPOSITORY] = $app->share(
            function ($app) {
                $dummyPlaceIds = [];
                if (isset($app['config']['bookable_event']['dummy_place_ids'])) {
                    $dummyPlaceIds = $app['config']['bookable_event']['dummy_place_ids'];
                }
                $repository = new DummyPlaceProjectionEnricher(
                    new CacheDocumentRepository(
                        $app['place_jsonld_cache']
                    ),
                    $dummyPlaceIds
                );

                $repository = new NilLocationEnrichedPlaceRepository(
                    new NilLocationNormalizer($app['place_iri_generator']),
                    $repository
                );

                $repository = new OfferMetadataEnrichedOfferRepository(
                    $app[OfferMetadataRepository::class],
                    $repository
                );

                $repository = new PopularityEnrichedOfferRepository(
                    $app[PopularityRepository::class],
                    $repository
                );

                if (isset($app['config']['polyfill_duplicate_places']) && $app['config']['polyfill_duplicate_places']) {
                    $repository = new DuplicatePlacesEnrichedPlaceRepository(
                        $app['duplicate_place_repository'],
                        $app['place_iri_generator'],
                        $repository
                    );
                }

                $repository = new PropertyPolyfillOfferRepository(
                    $repository,
                    $app[LabelServiceProvider::JSON_READ_REPOSITORY],
                    OfferType::place()
                );

                $repository = new TermLabelOfferRepositoryDecorator($repository, $app[TermRepository::class]);

                $repository = new MediaUrlOfferRepositoryDecorator($repository, $app['media_url_mapping']);

                return new BroadcastingDocumentRepositoryDecorator(
                    $repository,
                    $app['event_bus'],
                    $app[self::JSONLD_PROJECTED_EVENT_FACTORY]
                );
            }
        );

        $app['place_jsonld_cache'] = $app->share(
            function ($app) {
                return $app['cache']('place_jsonld');
            }
        );

        $app['place_cdbxml_importer'] = $app->share(
            function (Application $app) {
                return new CdbXMLImporter(
                    new CdbXMLItemBaseImporter(
                        new PriceDescriptionParser(
                            new NumberFormatRepository(),
                            new CurrencyRepository()
                        ),
                        $app['config']['base_price_translations']
                    ),
                    $app['calendar_factory'],
                    $app['cdbxml_contact_info_importer'],
                    $app[CdbXMLToJsonLDLabelImporter::class]
                );
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
