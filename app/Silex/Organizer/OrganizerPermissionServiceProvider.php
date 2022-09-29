<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Organizer;

use CultuurNet\UDB3\Organizer\ReadModel\Permission\Projector;
use CultuurNet\UDB3\Security\ResourceOwner\Doctrine\DBALResourceOwnerRepository;
use Silex\Application;
use Silex\ServiceProviderInterface;
use CultuurNet\UDB3\StringLiteral;

class OrganizerPermissionServiceProvider implements ServiceProviderInterface
{
    public const PERMISSION_PROJECTOR = 'organizer_permission.projector';
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app['organizer_owner.repository'] = $app->share(
            function (Application $app) {
                return new DBALResourceOwnerRepository(
                    new StringLiteral('organizer_permission_readmodel'),
                    $app['dbal_connection'],
                    new StringLiteral('organizer_id')
                );
            }
        );

        $app[self::PERMISSION_PROJECTOR] = $app->share(
            function (Application $app) {
                return new Projector(
                    $app['organizer_owner.repository'],
                    $app['cdbxml_created_by_resolver']
                );
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function boot(Application $app)
    {
    }
}
