<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
use CultuurNet\UDB3\Http\Import\ImportPriceInfoRequestBodyParser;
use CultuurNet\UDB3\Http\Import\ImportTermRequestBodyParser;
use CultuurNet\UDB3\Http\Import\RemoveEmptyArraysRequestBodyParser;
use CultuurNet\UDB3\Http\Place\GetEventsRequestHandler;
use CultuurNet\UDB3\Http\Place\ImportPlaceRequestHandler;
use CultuurNet\UDB3\Http\Place\LegacyPlaceRequestBodyParser;
use CultuurNet\UDB3\Http\Place\UpdateAddressRequestHandler;
use CultuurNet\UDB3\Http\Place\UpdateMajorInfoRequestHandler;
use CultuurNet\UDB3\Http\Request\Body\CombinedRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\ImagesPropertyPolyfillRequestBodyParser;
use CultuurNet\UDB3\Model\Import\Place\PlaceCategoryResolver;
use CultuurNet\UDB3\Model\Serializer\Place\PlaceDenormalizer;
use CultuurNet\UDB3\Place\ReadModel\Duplicate\CombinedLookupDuplicatePlace;
use CultuurNet\UDB3\Place\ReadModel\Duplicate\LookupDuplicatePlaceWithRedis;
use CultuurNet\UDB3\Place\ReadModel\Duplicate\LookupDuplicatePlaceWithSapi3;
use CultuurNet\UDB3\Place\ReadModel\Duplicate\UniqueAddressIdentifierFactory;
use CultuurNet\UDB3\Search\PlacesSapi3SearchService;
use CultuurNet\UDB3\User\CurrentUser;
use GuzzleHttp\Psr7\Uri;

final class PlaceRequestHandlerServiceProvider extends AbstractServiceProvider
{
    public const DUPLICATE_PLACE_IDENTIFIER = 'duplicate_place_identifier';

    protected function getProvidedServiceNames(): array
    {
        return [
            GetEventsRequestHandler::class,
            UpdateAddressRequestHandler::class,
            ImportPlaceRequestHandler::class,
            UpdateMajorInfoRequestHandler::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            GetEventsRequestHandler::class,
            function () use ($container) {
                return new GetEventsRequestHandler(
                    $container->get(EventRelationsRepository::class),
                );
            }
        );

        $container->addShared(
            UpdateAddressRequestHandler::class,
            function () use ($container) {
                return new UpdateAddressRequestHandler(
                    $container->get('event_command_bus')
                );
            }
        );

        $container->addShared(
            ImportPlaceRequestHandler::class,
            function () use ($container) {
                return new ImportPlaceRequestHandler(
                    $container->get('place_repository'),
                    new Version4Generator(),
                    new PlaceDenormalizer(),
                    new CombinedRequestBodyParser(
                        new LegacyPlaceRequestBodyParser(),
                        RemoveEmptyArraysRequestBodyParser::createForPlaces(),
                        new ImportTermRequestBodyParser(new PlaceCategoryResolver()),
                        new ImportPriceInfoRequestBodyParser($container->get('config')['base_price_translations']),
                        ImagesPropertyPolyfillRequestBodyParser::createForPlaces(
                            $container->get('media_object_iri_generator'),
                            $container->get('media_object_repository')
                        )
                    ),
                    $container->get('place_iri_generator'),
                    $container->get('event_command_bus'),
                    $container->get('import_image_collection_factory'),
                    $container->get('config')['prevent_duplicate_creation'] ?? false,
                    new CombinedLookupDuplicatePlace(
                        new LookupDuplicatePlaceWithRedis(
                            $container->get('cache')(self::DUPLICATE_PLACE_IDENTIFIER),
                            $container->get(UniqueAddressIdentifierFactory::class),
                            new Uri($container->get('config')['search']['v3']['base_url'] . '/places/'),
                            $container->get(CurrentUser::class)->getId(),
                        ),
                        new LookupDuplicatePlaceWithSapi3(
                            $container->get(PlacesSapi3SearchService::class),
                            $container->get(UniqueAddressIdentifierFactory::class),
                            $container->get(PlaceJSONLDServiceProvider::PLACE_JSONLD_REPOSITORY),
                            $container->get(CurrentUser::class)->getId(),
                        )
                    )
                );
            }
        );

        $container->addShared(
            UpdateMajorInfoRequestHandler::class,
            function () use ($container) {
                return new UpdateMajorInfoRequestHandler($container->get('event_command_bus'));
            }
        );

        $container->addShared(
            UniqueAddressIdentifierFactory::class,
            function () {
                return new UniqueAddressIdentifierFactory();
            }
        );
    }
}
