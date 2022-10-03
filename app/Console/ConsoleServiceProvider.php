<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Console\Command\ConsumeCommand;
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
    }
}
