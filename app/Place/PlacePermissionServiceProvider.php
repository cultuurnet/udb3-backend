<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Place;

use CultuurNet\UDB3\Security\ResourceOwner\Doctrine\DBALResourceOwnerRepository;
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
        $app['place_owner.repository'] = $app->share(
            function (Application $app) {
                return new DBALResourceOwnerRepository(
                    new StringLiteral('place_permission_readmodel'),
                    $app['dbal_connection'],
                    new StringLiteral('place_id')
                );
            }
        );

        $app['place_permission.projector'] = $app->share(
            function (Application $app) {
                $projector = new Projector(
                    $app['place_owner.repository'],
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
