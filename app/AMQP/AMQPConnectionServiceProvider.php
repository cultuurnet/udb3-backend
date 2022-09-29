<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\AMQP;

use League\Container\ServiceProvider\AbstractServiceProvider;
use PhpAmqpLib\Connection\AMQPStreamConnection;

final class AMQPConnectionServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        $services = [AMQPStreamConnection::class];
        return in_array($id, $services, true);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            AMQPStreamConnection::class,
            function () use ($container): AMQPStreamConnection {
                return new AMQPStreamConnection(
                    $container->get('amqp.connection.host'),
                    $container->get('amqp.connection.port'),
                    $container->get('amqp.connection.user'),
                    $container->get('amqp.connection.password'),
                    $container->get('amqp.connection.vhost')
                );
            }
        );
    }
}
