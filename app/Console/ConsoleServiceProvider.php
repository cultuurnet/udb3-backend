<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Console\Command\ConsumeCommand;
use CultuurNet\UDB3\Console\Command\EventAncestorsCommand;
use CultuurNet\UDB3\Console\Command\FireProjectedToJSONLDCommand;
use CultuurNet\UDB3\Console\Command\FireProjectedToJSONLDForRelationsCommand;
use CultuurNet\UDB3\Console\Command\GeocodeEventCommand;
use CultuurNet\UDB3\Console\Command\GeocodeOrganizerCommand;
use CultuurNet\UDB3\Console\Command\GeocodePlaceCommand;
use CultuurNet\UDB3\Console\Command\PurgeModelCommand;
use CultuurNet\UDB3\Console\Command\ReplayCommand;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
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
    }
}
