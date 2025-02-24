<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Doctrine\ReadModel\CacheDocumentRepository;
use CultuurNet\UDB3\Event\ReadModel\History\HistoryProjector;

final class EventHistoryServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            HistoryProjector::class,
            'event_history_repository',
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            HistoryProjector::class,
            function () use ($container): HistoryProjector {
                return new HistoryProjector($container->get('event_history_repository'));
            }
        );

        $container->addShared(
            'event_history_repository',
            function () use ($container): CacheDocumentRepository {
                return new CacheDocumentRepository($container->get('persistent_cache')('event_history'));
            }
        );
    }
}
