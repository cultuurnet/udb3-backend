<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Database;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Event\Listeners\SQLSessionInit;

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
                $eventManager = new EventManager();
                $sqlMode = 'NO_ENGINE_SUBSTITUTION,STRICT_ALL_TABLES';
                $query = "SET SESSION sql_mode = '{$sqlMode}'";
                $eventManager->addEventSubscriber(
                    new SQLSessionInit($query)
                );

                return DriverManager::getConnection(
                    $container->get('config')['database'],
                    null,
                    $eventManager
                );
            }
        );
    }
}
