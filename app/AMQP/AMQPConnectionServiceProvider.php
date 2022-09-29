<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\AMQP;

use CultuurNet\UDB3\Silex\Container\AbstractServiceProvider;
use PhpAmqpLib\Connection\AMQPStreamConnection;

final class AMQPConnectionServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return ['amqp.connection'];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'amqp.connection',
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
