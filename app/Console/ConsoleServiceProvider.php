<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console;

use Broadway\EventHandling\EventBus;
use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\Console\Command\BulkRemoveFromProduction;
use CultuurNet\UDB3\Console\Command\ChangeOfferOwner;
use CultuurNet\UDB3\Console\Command\ChangeOfferOwnerInBulk;
use CultuurNet\UDB3\Console\Command\ChangeOrganizerOwner;
use CultuurNet\UDB3\Console\Command\ChangeOrganizerOwnerInBulk;
use CultuurNet\UDB3\Console\Command\ConsumeCommand;
use CultuurNet\UDB3\Console\Command\ConvertDescriptionToEducationalDescriptionForCultuurkuur;
use CultuurNet\UDB3\Console\Command\DeletePlace;
use CultuurNet\UDB3\Console\Command\EventAncestorsCommand;
use CultuurNet\UDB3\Console\Command\ExcludeInvalidLabels;
use CultuurNet\UDB3\Console\Command\ExcludeLabel;
use CultuurNet\UDB3\Console\Command\ExecuteCommandFromCsv;
use CultuurNet\UDB3\Console\Command\FetchMoviesFromKinepolisApi;
use CultuurNet\UDB3\Console\Command\FindOutOfSyncProjections;
use CultuurNet\UDB3\Console\Command\FireProjectedToJSONLDCommand;
use CultuurNet\UDB3\Console\Command\FireProjectedToJSONLDForRelationsCommand;
use CultuurNet\UDB3\Console\Command\GeocodeEventCommand;
use CultuurNet\UDB3\Console\Command\GeocodeOrganizerCommand;
use CultuurNet\UDB3\Console\Command\GeocodePlaceCommand;
use CultuurNet\UDB3\Console\Command\ImportMovieIdsFromCsv;
use CultuurNet\UDB3\Console\Command\ImportOfferAutoClassificationLabels;
use CultuurNet\UDB3\Console\Command\IncludeLabel;
use CultuurNet\UDB3\Console\Command\KeycloakCommand;
use CultuurNet\UDB3\Console\Command\MoveEvents;
use CultuurNet\UDB3\Console\Command\ProcessDuplicatePlaces;
use CultuurNet\UDB3\Console\Command\PurgeModelCommand;
use CultuurNet\UDB3\Console\Command\ReindexEventsWithRecommendations;
use CultuurNet\UDB3\Console\Command\ReindexOffersWithPopularityScore;
use CultuurNet\UDB3\Console\Command\RemoveFacilitiesFromPlace;
use CultuurNet\UDB3\Console\Command\RemoveLabelOffer;
use CultuurNet\UDB3\Console\Command\RemoveLabelOrganizer;
use CultuurNet\UDB3\Console\Command\ReplaceNewsArticlePublisher;
use CultuurNet\UDB3\Console\Command\ReplayCommand;
use CultuurNet\UDB3\Console\Command\UpdateBookingAvailabilityCommand;
use CultuurNet\UDB3\Console\Command\UpdateEventsAttendanceMode;
use CultuurNet\UDB3\Console\Command\UpdateOfferStatusCommand;
use CultuurNet\UDB3\Console\Command\UpdateUniqueLabels;
use CultuurNet\UDB3\Console\Command\UpdateUniqueOrganizers;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Doctrine\ReadModel\CacheDocumentRepository;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Event\Productions\ProductionRepository;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
use CultuurNet\UDB3\Kinepolis\Client\AuthenticatedKinepolisClient;
use CultuurNet\UDB3\Kinepolis\KinepolisService;
use CultuurNet\UDB3\Kinepolis\Mapping\MovieMappingRepository;
use CultuurNet\UDB3\Kinepolis\Parser\KinepolisDateParser;
use CultuurNet\UDB3\Kinepolis\Parser\KinepolisMovieParser;
use CultuurNet\UDB3\Kinepolis\Parser\KinepolisPriceParser;
use CultuurNet\UDB3\Kinepolis\Trailer\YoutubeTrailerRepository;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Organizer\WebsiteNormalizer;
use CultuurNet\UDB3\Search\EventsSapi3SearchService;
use CultuurNet\UDB3\Search\OrganizersSapi3SearchService;
use CultuurNet\UDB3\Search\PlacesSapi3SearchService;
use CultuurNet\UDB3\User\Keycloak\KeycloakUserIdentityResolver;
use Google_Client;
use Google_Service_YouTube;
use Http\Adapter\Guzzle7\Client;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;

final class ConsoleServiceProvider extends AbstractServiceProvider
{
    private const COMMAND_SERVICES = [
        'console.amqp-listen-uitpas',
        'console.replay',
        'console.find-out-of-sync-projections',
        'console.event:ancestors',
        'console.purge',
        'console.place:geocode',
        'console.place:delete',
        'console.event:geocode',
        'console.event:move',
        'console.organizer:geocode',
        'console.fire-projected-to-jsonld-for-relations',
        'console.fire-projected-to-jsonld',
        'console.place:process-duplicates',
        'console.event:bulk-remove-from-production',
        'console.event:reindex-offers-with-popularity',
        'console.place:reindex-offers-with-popularity',
        'console.event:reindex-events-with-recommendations',
        'console.event:status:update',
        'console.place:status:update',
        'console.event:booking-availability:update',
        'console.event:attendanceMode:update',
        'console.offer:change-owner',
        'console.offer:change-owner-bulk',
        'console.organizer:change-owner',
        'console.organizer:change-owner-bulk',
        'console.label:exclude',
        'console.label:exclude-invalid',
        'console.label:include',
        'console.keycloak:find-user',
        'console.label:update-unique',
        'console.organizer:update-unique',
        'console.place:facilities:remove',
        'console.offer:remove-label',
        'console.organizer:remove-label',
        'console.offer:import-auto-classification-labels',
        'console.article:replace-publisher',
        'console.organizer:convert-educational-description',
        'console.execute-command-from-csv',
        'console.movies:fetch',
        'console.movies:migrate',
    ];

    protected function getProvidedServiceNames(): array
    {
        return array_merge(
            self::COMMAND_SERVICES,
            [CommandLoaderInterface::class]
        );
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            CommandLoaderInterface::class,
            function () use ($container): CommandLoaderInterface {
                $commandServiceNames = self::COMMAND_SERVICES;

                // Remove the "console." prefix from every command service name to get the actual command names without
                // loading them.
                $commandNames = array_map(
                    fn (string $commandServiceName) => substr($commandServiceName, strlen('console.')),
                    $commandServiceNames
                );

                $commandMap = array_combine($commandNames, $commandServiceNames);

                return new ContainerCommandLoader($container, $commandMap);
            }
        );

        $container->addShared(
            'console.amqp-listen-uitpas',
            function () use ($container) {
                $heartBeat = static function () use ($container): void {
                    $db = $container->get('dbal_connection');
                    $db->query('SELECT 1')->execute();
                };

                return new ConsumeCommand(
                    'amqp-listen-uitpas',
                    'amqp.uitpas_event_bus_forwarding_consumer',
                    $container,
                    $heartBeat
                );
            }
        );

        $container->addShared(
            'console.replay',
            fn () => new ReplayCommand(
                $container->get('event_command_bus'),
                $container->get('dbal_connection'),
                $container->get('eventstore_payload_serializer'),
                $container->get(EventBus::class)
            )
        );

        $container->addShared(
            'console.find-out-of-sync-projections',
            function () use ($container) {
                return new FindOutOfSyncProjections(
                    $container->get('dbal_connection'),
                    $container->get('event_jsonld_repository'),
                    $container->get('place_jsonld_repository'),
                    $container->get('organizer_jsonld_repository')
                );
            }
        );

        $container->addShared(
            'console.event:ancestors',
            fn () => new EventAncestorsCommand($container->get('event_command_bus'), $container->get('event_store'))
        );

        $container->addShared(
            'console.purge',
            fn () => new PurgeModelCommand($container->get('dbal_connection'))
        );

        $container->addShared(
            'console.place:geocode',
            fn () => new GeocodePlaceCommand(
                $container->get('event_command_bus'),
                $container->get(PlacesSapi3SearchService::class),
                $container->get('place_jsonld_repository')
            )
        );

        $container->addShared(
            'console.event:geocode',
            fn () => new GeocodeEventCommand(
                $container->get('event_command_bus'),
                $container->get(EventsSapi3SearchService::class),
                $container->get('event_jsonld_repository')
            )
        );

        $container->addShared(
            'console.organizer:geocode',
            fn () => new GeocodeOrganizerCommand(
                $container->get('event_command_bus'),
                $container->get(OrganizersSapi3SearchService::class),
                $container->get('organizer_jsonld_repository')
            )
        );

        $container->addShared(
            'console.event:move',
            fn () => new MoveEvents(
                $container->get('event_command_bus'),
                $container->get(EventsSapi3SearchService::class),
            )
        );

        $container->addShared(
            'console.fire-projected-to-jsonld-for-relations',
            fn () => new FireProjectedToJSONLDForRelationsCommand(
                $container->get(EventBus::class),
                $container->get('dbal_connection'),
                $container->get('organizer_jsonld_projected_event_factory'),
                $container->get('place_jsonld_projected_event_factory')
            )
        );

        $container->addShared(
            'console.fire-projected-to-jsonld',
            fn () => new FireProjectedToJSONLDCommand(
                $container->get(EventBus::class),
                $container->get('organizer_jsonld_projected_event_factory'),
                $container->get('place_jsonld_projected_event_factory')
            )
        );

        $container->addShared(
            'console.place:process-duplicates',
            fn () => new ProcessDuplicatePlaces(
                $container->get('event_command_bus'),
                $container->get('duplicate_place_repository'),
                $container->get('canonical_service'),
                $container->get(EventBus::class),
                $container->get('place_jsonld_projected_event_factory'),
                $container->get(EventRelationsRepository::class),
                $container->get('dbal_connection')
            )
        );

        $container->addShared(
            'console.event:bulk-remove-from-production',
            fn () => new BulkRemoveFromProduction($container->get('event_command_bus'))
        );

        $container->addShared(
            'console.event:reindex-offers-with-popularity',
            fn () => new ReindexOffersWithPopularityScore(
                OfferType::event(),
                $container->get('dbal_connection'),
                $container->get(EventBus::class),
                $container->get('event_jsonld_projected_event_factory')
            )
        );

        $container->addShared(
            'console.place:reindex-offers-with-popularity',
            fn () => new ReindexOffersWithPopularityScore(
                OfferType::place(),
                $container->get('dbal_connection'),
                $container->get(EventBus::class),
                $container->get('place_jsonld_projected_event_factory')
            )
        );

        $container->addShared(
            'console.event:reindex-events-with-recommendations',
            fn () => new ReindexEventsWithRecommendations(
                $container->get('dbal_connection'),
                $container->get(EventBus::class),
                $container->get('event_jsonld_projected_event_factory')
            )
        );

        $container->addShared(
            'console.event:status:update',
            fn () => new UpdateOfferStatusCommand(
                OfferType::event(),
                $container->get('event_command_bus'),
                $container->get(EventsSapi3SearchService::class)
            )
        );

        $container->addShared(
            'console.place:status:update',
            fn () => new UpdateOfferStatusCommand(
                OfferType::place(),
                $container->get('event_command_bus'),
                $container->get(PlacesSapi3SearchService::class)
            )
        );

        $container->addShared(
            'console.event:booking-availability:update',
            fn () => new UpdateBookingAvailabilityCommand(
                $container->get('event_command_bus'),
                $container->get(EventsSapi3SearchService::class)
            )
        );

        $container->addShared(
            'console.event:attendanceMode:update',
            fn () => new UpdateEventsAttendanceMode(
                $container->get('event_command_bus'),
                $container->get(EventsSapi3SearchService::class)
            )
        );

        $container->addShared(
            'console.offer:change-owner',
            fn () => new ChangeOfferOwner($container->get('event_command_bus'))
        );

        $container->addShared(
            'console.offer:change-owner-bulk',
            fn () => new ChangeOfferOwnerInBulk(
                $container->get('event_command_bus'),
                $container->get('offer_owner_query')
            )
        );

        $container->addShared(
            'console.organizer:change-owner',
            fn () => new ChangeOrganizerOwner($container->get('event_command_bus'))
        );

        $container->addShared(
            'console.organizer:change-owner-bulk',
            fn () => new ChangeOrganizerOwnerInBulk(
                $container->get('event_command_bus'),
                $container->get('organizer_owner.repository')
            )
        );

        $container->addShared(
            'console.label:exclude',
            fn () => new ExcludeLabel($container->get('event_command_bus'))
        );

        $container->addShared(
            'console.label:exclude-invalid',
            fn () => new ExcludeInvalidLabels($container->get('event_command_bus'), $container->get('dbal_connection'))
        );

        $container->addShared(
            'console.label:include',
            fn () => new IncludeLabel($container->get('event_command_bus'))
        );

        $container->addShared(
            'console.keycloak:find-user',
            fn () => new KeycloakCommand(
                $container->get(KeycloakUserIdentityResolver::class)
            )
        );

        $container->addShared(
            'console.label:update-unique',
            fn () => new UpdateUniqueLabels($container->get('dbal_connection'))
        );

        $container->addShared(
            'console.organizer:update-unique',
            fn () => new UpdateUniqueOrganizers($container->get('dbal_connection'), new WebsiteNormalizer())
        );

        $container->addShared(
            'console.place:facilities:remove',
            fn () => new RemoveFacilitiesFromPlace(
                $container->get('event_command_bus'),
                $container->get(PlacesSapi3SearchService::class)
            )
        );

        $container->addShared(
            'console.offer:remove-label',
            fn () => new RemoveLabelOffer($container->get('dbal_connection'), $container->get('event_command_bus'))
        );

        $container->addShared(
            'console.organizer:remove-label',
            fn () => new RemoveLabelOrganizer(
                $container->get('dbal_connection'),
                $container->get('event_command_bus')
            )
        );

        $container->addShared(
            'console.offer:import-auto-classification-labels',
            fn () => new ImportOfferAutoClassificationLabels(
                $container->get('dbal_connection'),
                $container->get('event_command_bus')
            )
        );

        $container->addShared(
            'console.article:replace-publisher',
            fn () => new ReplaceNewsArticlePublisher($container->get('dbal_connection'))
        );

        $container->addShared(
            'console.organizer:convert-educational-description',
            fn () => new ConvertDescriptionToEducationalDescriptionForCultuurkuur(
                $container->get('event_command_bus'),
                $container->get(OrganizersSapi3SearchService::class),
                new CacheDocumentRepository($container->get('organizer_jsonld_cache'))
            )
        );

        $container->addShared(
            'console.place:delete',
            fn () => new DeletePlace(
                $container->get('event_command_bus'),
                $container->get(EventRelationsRepository::class),
                $container->get('place_jsonld_repository'),
            )
        );

        $container->addShared(
            'console.execute-command-from-csv',
            fn () => new ExecuteCommandFromCsv()
        );

        $container->addShared(
            'console.movies:migrate',
            fn () => new ImportMovieIdsFromCsv(
                new MovieMappingRepository($container->get(('dbal_connection'))),
                $container->get('event_jsonld_repository')
            )
        );

        $container->addShared(
            'console.movies:fetch',
            fn () => new FetchMoviesFromKinepolisApi(
                new KinepolisService(
                    $container->get('event_command_bus'),
                    $container->get('event_repository'),
                    new AuthenticatedKinepolisClient(
                        $container->get('config')['kinepolis']['url'],
                        new Client(),
                        $container->get('config')['kinepolis']['authentication']['key'],
                        $container->get('config')['kinepolis']['authentication']['secret'],
                    ),
                    new KinepolisMovieParser(
                        $container->get('config')['kinepolis']['terms'],
                        $container->get('config')['kinepolis']['theaters'],
                        new KinepolisDateParser()
                    ),
                    new KinepolisPriceParser(),
                    new MovieMappingRepository($container->get(('dbal_connection'))),
                    $container->get('image_uploader'),
                    new Version4Generator(),
                    new YoutubeTrailerRepository(
                        new Google_Service_YouTube(
                            new Google_Client(
                                [
                                    'application_name' => 'UiTDatabankTrailerFinder',
                                    'developer_key' => $container->get('config')['kinepolis']['trailers']['developer_key'],
                                ]
                            )
                        ),
                        $container->get('config')['kinepolis']['trailers']['channel_id'],
                        new Version4Generator()
                    ),
                    $container->get(ProductionRepository::class),
                    LoggerFactory::create(
                        $container,
                        LoggerName::forService('fetching-movies', 'kinepolis')
                    )
                ),
            )
        );
    }
}
