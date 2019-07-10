<?php

namespace CultuurNet\UDB3\Silex\MyOrganizers;

use CultuurNet\UDB3\MyOrganizers\ReadModel\Doctrine\DBALLookupService;
use CultuurNet\UDB3\MyOrganizers\ReadModel\Doctrine\DBALRepository;
use CultuurNet\UDB3\MyOrganizers\ReadModel\Projector;
use CultuurNet\UDB3\MyOrganizers\ReadModel\UDB2Projector;
use CultuurNet\UDB3\Silex\User\UserServiceProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;

class MyOrganizersServiceProvider implements ServiceProviderInterface
{
    public const PROJECTOR = 'my_organizers.projector';
    public const UDB2_PROJECTOR = 'my_organizers.udb2.projector';

    public const LOOKUP = 'my_organizers.lookup';

    private const REPOSITORY = 'my_organizers.repository';

    private const TABLE_NAME = 'my_organizers.table_name';

    public function register(Application $app)
    {
        $app[self::TABLE_NAME] = new StringLiteral('my_organizers');

        $app[self::REPOSITORY] = $app->share(
            function (Application $app) {
                return new DBALRepository(
                    $app['dbal_connection'],
                    $app[self::TABLE_NAME]
                );
            }
        );

        $app[self::PROJECTOR] = $app->share(
            function (Application $app) {
                return new Projector(
                    $app[self::REPOSITORY]
                );
            }
        );

        $app[self::UDB2_PROJECTOR] = $app->share(
            function (Application $app) {
                return new UDB2Projector(
                    $app[self::REPOSITORY],
                    $app[UserServiceProvider::ITEM_BASE_ADAPTER_FACTORY]
                );
            }
        );

        $app[self::LOOKUP] = $app->share(
            function (Application $app) {
                return new DBALLookupService(
                    $app['dbal_connection'],
                    $app[self::TABLE_NAME],
                    $app['organizer_iri_generator']
                );
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
