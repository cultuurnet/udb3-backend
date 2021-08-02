<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Event;

use CultuurNet\UDB3\Event\ReadModel\Permission\Projector;
use CultuurNet\UDB3\Security\ResourceOwner\Doctrine\DBALResourceOwnerRepository;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;

class EventPermissionServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app['event_owner.repository'] = $app->share(
            function (Application $app) {
                return new DBALResourceOwnerRepository(
                    new StringLiteral('event_permission_readmodel'),
                    $app['dbal_connection'],
                    new StringLiteral('event_id')
                );
            }
        );

        $app['event_permission.projector'] = $app->share(
            function (Application $app) {
                return new Projector(
                    $app['event_owner.repository'],
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
