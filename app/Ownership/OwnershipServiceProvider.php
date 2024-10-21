<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\AggregateType;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Doctrine\ReadModel\CacheDocumentRepository;
use CultuurNet\UDB3\Ownership\Readmodels\Name\DocumentItemNameResolver;
use CultuurNet\UDB3\Ownership\Readmodels\OwnershipLDProjector;
use CultuurNet\UDB3\Ownership\Readmodels\OwnershipPermissionProjector;
use CultuurNet\UDB3\Ownership\Readmodels\OwnershipSearchProjector;
use CultuurNet\UDB3\Ownership\Repositories\Search\DBALOwnershipSearchRepository;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use Ramsey\Uuid\UuidFactory;

final class OwnershipServiceProvider extends AbstractServiceProvider
{
    public const OWNERSHIP_JSONLD_REPOSITORY = 'ownership_jsonld_repository';

    protected function getProvidedServiceNames(): array
    {
        return [
            OwnershipRepository::class,
            OwnershipServiceProvider::OWNERSHIP_JSONLD_REPOSITORY,
            OwnershipLDProjector::class,
            OwnershipSearchRepository::class,
            OwnershipSearchProjector::class,
            OwnershipPermissionProjector::class,
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
            OwnershipServiceProvider::OWNERSHIP_JSONLD_REPOSITORY,
            fn () => new CacheDocumentRepository(
                $container->get('cache')('ownership_jsonld'),
            )
        );

        $container->addShared(
            OwnershipLDProjector::class,
            fn () => new OwnershipLDProjector(
                $container->get(OwnershipServiceProvider::OWNERSHIP_JSONLD_REPOSITORY)
            )
        );

        $container->addShared(
            OwnershipSearchRepository::class,
            fn () => new DBALOwnershipSearchRepository($this->container->get('dbal_connection'))
        );

        $container->addShared(
            OwnershipSearchProjector::class,
            fn () => new OwnershipSearchProjector(
                $container->get(OwnershipSearchRepository::class)
            )
        );

        $container->addShared(
            OwnershipPermissionProjector::class,
            fn () => new OwnershipPermissionProjector(
                $container->get('authorized_command_bus'),
                $container->get(OwnershipSearchRepository::class),
                new UuidFactory(),
                new DocumentItemNameResolver($container->get('organizer_jsonld_repository'))
            )
        );
    }
}
