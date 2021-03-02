<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Organizer;

use CultuurNet\UDB3\Organizer\ReadModel\Permission\Projector;
use CultuurNet\UDB3\Offer\ReadModel\Permission\Doctrine\DBALRepository;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;

class OrganizerPermissionServiceProvider implements ServiceProviderInterface
{
    public const PERMISSION_PROJECTOR = 'organizer_permission.projector';
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app['organizer_permission.table_name'] = new StringLiteral('organizer_permission_readmodel');
        $app['organizer_permission.id_field'] = new StringLiteral('organizer_id');

        $app['organizer_permission.repository'] = $app->share(
            function (Application $app) {
                return new DBALRepository(
                    $app['organizer_permission.table_name'],
                    $app['dbal_connection'],
                    $app['organizer_permission.id_field']
                );
            }
        );

        $app[self::PERMISSION_PROJECTOR] = $app->share(
            function (Application $app) {
                $projector = new Projector(
                    $app['organizer_permission.repository'],
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
