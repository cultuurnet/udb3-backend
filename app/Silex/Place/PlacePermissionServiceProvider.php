<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Place;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Security\ResourceOwner\Doctrine\DBALResourceOwnerRepository;
use CultuurNet\UDB3\Place\ReadModel\Permission\Projector;
use CultuurNet\UDB3\StringLiteral;

final class PlacePermissionServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'place_owner.repository',
            'place_permission.projector',
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'place_owner.repository',
            function () use ($container) {
                return new DBALResourceOwnerRepository(
                    new StringLiteral('place_permission_readmodel'),
                    $container->get('dbal_connection'),
                    new StringLiteral('place_id')
                );
            }
        );

        $container->addShared(
            'place_permission.projector',
            function () use ($container) {
                return new Projector(
                    $container->get('place_owner.repository'),
                    $container->get('cdbxml_created_by_resolver')
                );
            }
        );
    }
}
