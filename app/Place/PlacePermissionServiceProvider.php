<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex\Place;

use CultuurNet\UDB3\Offer\ReadModel\Permission\Doctrine\DBALRepository;
use CultuurNet\UDB3\Place\ReadModel\Permission\Projector;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;

class PlacePermissionServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app['place_permission.table_name'] = new StringLiteral('place_permission_readmodel');
        $app['place_permission.id_field'] = new StringLiteral('place_id');

        $app['place_permission.repository'] = $app->share(
            function (Application $app) {
                return new DBALRepository(
                    $app['place_permission.table_name'],
                    $app['dbal_connection'],
                    $app['place_permission.id_field']
                );
            }
        );

        $app['place_permission.projector'] = $app->share(
            function (Application $app) {
                $projector = new Projector(
                    $app['place_permission.repository'],
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
