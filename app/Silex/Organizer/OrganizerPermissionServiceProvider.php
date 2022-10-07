<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Organizer;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Organizer\ReadModel\Permission\Projector;
use CultuurNet\UDB3\Security\ResourceOwner\Doctrine\DBALResourceOwnerRepository;
use CultuurNet\UDB3\StringLiteral;

final class OrganizerPermissionServiceProvider extends AbstractServiceProvider
{
    public const PERMISSION_PROJECTOR = 'organizer_permission.projector';

    protected function getProvidedServiceNames(): array
    {
        return [
            'organizer_owner.repository',
            self::PERMISSION_PROJECTOR,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'organizer_owner.repository',
            function () use ($container) {
                return new DBALResourceOwnerRepository(
                    new StringLiteral('organizer_permission_readmodel'),
                    $container->get('dbal_connection'),
                    new StringLiteral('organizer_id')
                );
            }
        );

        $container->addShared(
            self::PERMISSION_PROJECTOR,
            function () use ($container) {
                return new Projector(
                    $container->get('organizer_owner.repository'),
                    $container->get('cdbxml_created_by_resolver')
                );
            }
        );
    }
}
