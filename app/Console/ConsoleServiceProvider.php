<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Console\Command\ChangeOfferOwner;
use CultuurNet\UDB3\Console\Command\ChangeOfferOwnerInBulk;
use CultuurNet\UDB3\Console\Command\ChangeOrganizerOwner;
use CultuurNet\UDB3\Console\Command\ChangeOrganizerOwnerInBulk;
use CultuurNet\UDB3\Console\Command\ConsumeCommand;
use CultuurNet\UDB3\Console\Command\EventAncestorsCommand;
use CultuurNet\UDB3\Console\Command\FireProjectedToJSONLDCommand;
use CultuurNet\UDB3\Console\Command\FireProjectedToJSONLDForRelationsCommand;
use CultuurNet\UDB3\Console\Command\GeocodeEventCommand;
use CultuurNet\UDB3\Console\Command\GeocodeOrganizerCommand;
use CultuurNet\UDB3\Console\Command\GeocodePlaceCommand;
use CultuurNet\UDB3\Console\Command\ImportOfferAutoClassificationLabels;
use CultuurNet\UDB3\Console\Command\ProcessDuplicatePlaces;
use CultuurNet\UDB3\Console\Command\PurgeModelCommand;
use CultuurNet\UDB3\Console\Command\ReindexEventsWithRecommendations;
use CultuurNet\UDB3\Console\Command\ReindexOffersWithPopularityScore;
use CultuurNet\UDB3\Console\Command\RemoveFacilitiesFromPlace;
use CultuurNet\UDB3\Console\Command\RemoveLabelOffer;
use CultuurNet\UDB3\Console\Command\RemoveLabelOrganizer;
use CultuurNet\UDB3\Console\Command\ReplayCommand;
use CultuurNet\UDB3\Console\Command\UpdateBookingAvailabilityCommand;
use CultuurNet\UDB3\Console\Command\UpdateEventsAttendanceMode;
use CultuurNet\UDB3\Console\Command\UpdateOfferStatusCommand;
use CultuurNet\UDB3\Console\Command\UpdateUniqueLabels;
use CultuurNet\UDB3\Console\Command\UpdateUniqueOrganizers;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Organizer\WebsiteNormalizer;
use Doctrine\DBAL\Driver\Connection;

final class ConsoleServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'console.amqp-listen-uitpas',
            'console.replay',
            'console.event:ancestors',
            'console.purge',
            'console.place:geocode',
            'console.event:geocode',
            'console.organizer:geocode',
            'console.fire-projected-to-jsonld-for-relations',
            'console.fire-projected-to-jsonld',
            'console.place:process-duplicates',
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
            'console.label:update-unique',
            'console.organizer:update-unique',
            'console.place:facilities:remove',
            'console.offer:remove-label',
            'console.organizer:remove-label',
            'console.offer:import-auto-classification-labels',
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'console.amqp-listen-uitpas',
            function () use ($container) {
                $heartBeat = static function () use ($container) {
                    /** @var Connection $db */
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
            function () use ($container) {
                return new ReplayCommand(
                    $container->get('event_command_bus'),
                    $container->get('dbal_connection'),
                    $container->get('eventstore_payload_serializer'),
                    $container->get(EventBus::class)
                );
            }
        );

        $container->addShared(
            'console.event:ancestors',
            function () use ($container) {
                return new EventAncestorsCommand($container->get('event_command_bus'), $container->get('event_store'));
            }
        );

        $container->addShared(
            'console.purge',
            function () use ($container) {
                return new PurgeModelCommand($container->get('dbal_connection'));
            }
        );

        $container->addShared(
            'console.place:geocode',
            function () use ($container) {
                return new GeocodePlaceCommand(
                    $container->get('event_command_bus'),
                    $container->get('sapi3_search_service_places'),
                    $container->get('place_jsonld_repository')
                );
            }
        );

        $container->addShared(
            'console.event:geocode',
            function () use ($container) {
                return new GeocodeEventCommand(
                    $container->get('event_command_bus'),
                    $container->get('sapi3_search_service_events'),
                    $container->get('event_jsonld_repository')
                );
            }
        );

        $container->addShared(
            'console.organizer:geocode',
            function () use ($container) {
                return new GeocodeOrganizerCommand(
                    $container->get('event_command_bus'),
                    $container->get('sapi3_search_service_organizers'),
                    $container->get('organizer_jsonld_repository')
                );
            }
        );

        $container->addShared(
            'console.fire-projected-to-jsonld-for-relations',
            function () use ($container) {
                return new FireProjectedToJSONLDForRelationsCommand(
                    $container->get(EventBus::class),
                    $container->get('dbal_connection'),
                    $container->get('organizer_jsonld_projected_event_factory'),
                    $container->get('place_jsonld_projected_event_factory')
                );
            }
        );

        $container->addShared(
            'console.fire-projected-to-jsonld',
            function () use ($container) {
                return new FireProjectedToJSONLDCommand(
                    $container->get(EventBus::class),
                    $container->get('organizer_jsonld_projected_event_factory'),
                    $container->get('place_jsonld_projected_event_factory')
                );
            }
        );

        $container->addShared(
            'console.place:process-duplicates',
            function () use ($container) {
                return new ProcessDuplicatePlaces(
                    $container->get('event_command_bus'),
                    $container->get('duplicate_place_repository'),
                    $container->get('canonical_service'),
                    $container->get(EventBus::class),
                    $container->get('place_jsonld_projected_event_factory'),
                    $container->get(EventRelationsRepository::class),
                    $container->get('dbal_connection')
                );
            }
        );

        $container->addShared(
            'console.event:reindex-offers-with-popularity',
            function () use ($container) {
                return new ReindexOffersWithPopularityScore(
                    OfferType::event(),
                    $container->get('dbal_connection'),
                    $container->get(EventBus::class),
                    $container->get('event_jsonld_projected_event_factory')
                );
            }
        );

        $container->addShared(
            'console.place:reindex-offers-with-popularity',
            function () use ($container) {
                return new ReindexOffersWithPopularityScore(
                    OfferType::place(),
                    $container->get('dbal_connection'),
                    $container->get(EventBus::class),
                    $container->get('place_jsonld_projected_event_factory')
                );
            }
        );

        $container->addShared(
            'console.event:reindex-events-with-recommendations',
            function () use ($container) {
                return new ReindexEventsWithRecommendations(
                    $container->get('dbal_connection'),
                    $container->get(EventBus::class),
                    $container->get('event_jsonld_projected_event_factory')
                );
            }
        );

        $container->addShared(
            'console.event:status:update',
            function () use ($container) {
                return new UpdateOfferStatusCommand(
                    OfferType::event(),
                    $container->get('event_command_bus'),
                    $container->get('sapi3_search_service_events')
                );
            }
        );

        $container->addShared(
            'console.place:status:update',
            function () use ($container) {
                return new UpdateOfferStatusCommand(
                    OfferType::place(),
                    $container->get('event_command_bus'),
                    $container->get('sapi3_search_service_places')
                );
            }
        );

        $container->addShared(
            'console.event:booking-availability:update',
            function () use ($container) {
                return new UpdateBookingAvailabilityCommand(
                    $container->get('event_command_bus'),
                    $container->get('sapi3_search_service_events')
                );
            }
        );

        $container->addShared(
            'console.event:attendanceMode:update',
            function () use ($container) {
                return new UpdateEventsAttendanceMode(
                    $container->get('event_command_bus'),
                    $container->get('sapi3_search_service_events')
                );
            }
        );

        $container->addShared(
            'console.offer:change-owner',
            function () use ($container) {
                return new ChangeOfferOwner($container->get('event_command_bus'));
            }
        );

        $container->addShared(
            'console.offer:change-owner-bulk',
            function () use ($container) {
                return new ChangeOfferOwnerInBulk(
                    $container->get('event_command_bus'),
                    $container->get('offer_owner_query')
                );
            }
        );

        $container->addShared(
            'console.organizer:change-owner',
            function () use ($container) {
                return new ChangeOrganizerOwner($container->get('event_command_bus'));
            }
        );

        $container->addShared(
            'console.organizer:change-owner-bulk',
            function () use ($container) {
                return new ChangeOrganizerOwnerInBulk(
                    $container->get('event_command_bus'),
                    $container->get('organizer_owner.repository')
                );
            }
        );

        $container->addShared(
            'console.label:update-unique',
            function () use ($container) {
                return new UpdateUniqueLabels($container->get('dbal_connection'));
            }
        );

        $container->addShared(
            'console.organizer:update-unique',
            function () use ($container) {
                return new UpdateUniqueOrganizers($container->get('dbal_connection'), new WebsiteNormalizer());
            }
        );

        $container->addShared(
            'console.place:facilities:remove',
            function () use ($container) {
                return new RemoveFacilitiesFromPlace(
                    $container->get('event_command_bus'),
                    $container->get('sapi3_search_service_places')
                );
            }
        );

        $container->addShared(
            'console.offer:remove-label',
            function () use ($container) {
                return new RemoveLabelOffer($container->get('dbal_connection'), $container->get('event_command_bus'));
            }
        );

        $container->addShared(
            'console.organizer:remove-label',
            function () use ($container) {
                return new RemoveLabelOrganizer(
                    $container->get('dbal_connection'),
                    $container->get('event_command_bus')
                );
            }
        );

        $container->addShared(
            'console.offer:import-auto-classification-labels',
            function () use ($container) {
                return new ImportOfferAutoClassificationLabels(
                    $container->get('dbal_connection'),
                    $container->get('event_command_bus')
                );
            }
        );
    }
}
