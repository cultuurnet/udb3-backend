<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\AggregateType;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Doctrine\ReadModel\CacheDocumentRepository;
use CultuurNet\UDB3\Ownership\Readmodels\OwnershipLDProjector;

final class OwnershipServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            OwnershipRepository::class,
            'ownership_jsonld_repository',
            OwnershipLDProjector::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            OwnershipRepository::class,
            fn () => new OwnershipRepository(
                $container->get('event_store_factory')(AggregateType::ownership()),
                $container->get(EventBus::class),
                [
                    $container->get('event_stream_metadata_enricher'),
                ]
            )
        );

        $container->addShared(
            'ownership_jsonld_repository',
            fn () => new CacheDocumentRepository(
                $container->get('cache')('ownership_jsonld'),
            )
        );

        $container->addShared(
            OwnershipLDProjector::class,
            fn () => new OwnershipLDProjector(
                $container->get('ownership_jsonld_repository')
            )
        );
    }
}
