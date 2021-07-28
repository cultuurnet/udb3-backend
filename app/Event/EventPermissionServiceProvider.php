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
        $app['event_permission.table_name'] = new StringLiteral('event_permission_readmodel');
        $app['event_permission.id_field'] = new StringLiteral('event_id');

        $app['event_permission.repository'] = $app->share(
            function (Application $app) {
                return new DBALResourceOwnerRepository(
                    $app['event_permission.table_name'],
                    $app['dbal_connection'],
                    $app['event_permission.id_field']
                );
            }
        );

        $app['event_permission.projector'] = $app->share(
            function (Application $app) {
                $projector = new Projector(
                    $app['event_permission.repository'],
                    $app['cdbxml_created_by_resolver']
                );

                return $projector;
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
