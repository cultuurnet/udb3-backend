<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Doctrine\ReadModel\CacheDocumentRepository;
use CultuurNet\UDB3\Place\ReadModel\History\HistoryProjector;

final class PlaceHistoryServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            HistoryProjector::class,
            'places_history_repository',
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            HistoryProjector::class,
            function () use ($container) {
                return new HistoryProjector(
                    $container->get('places_history_repository')
                );
            }
        );

        $container->addShared(
            'places_history_repository',
            function () use ($container) {
                return new CacheDocumentRepository(
                    $container->get('cache')('place_history')
                );
            }
        );
    }
}
