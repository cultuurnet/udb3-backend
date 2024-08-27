<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\AMQP;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use PhpAmqpLib\Connection\AMQPStreamConnection;

final class AMQPConnectionServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [AMQPStreamConnection::class];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            AMQPStreamConnection::class,
            function () use ($container): AMQPStreamConnection {
                return new AMQPStreamConnection(
                    $container->get('config')['amqp']['host'],
                    $container->get('config')['amqp']['port'],
                    $container->get('config')['amqp']['user'],
                    $container->get('config')['amqp']['password'],
                    $container->get('config')['amqp']['vhost']
                );
            }
        );
    }
}
