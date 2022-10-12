<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Database;

use CultuurNet\UDB3\Container\AbstractServiceProvider;

final class DatabaseServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'dbal_connection',
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'dbal_connection',
            function () use ($container) {
                $eventManager = new \Doctrine\Common\EventManager();
                $sqlMode = 'NO_ENGINE_SUBSTITUTION,STRICT_ALL_TABLES';
                $query = "SET SESSION sql_mode = '{$sqlMode}'";
                $eventManager->addEventSubscriber(
                    new \Doctrine\DBAL\Event\Listeners\SQLSessionInit($query)
                );

                $connection = \Doctrine\DBAL\DriverManager::getConnection(
                    $container->get('config')['database'],
                    null,
                    $eventManager
                );

                return $connection;
            }
        );
    }
}
