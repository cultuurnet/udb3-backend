<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\Silex\Console\PurgeModelCommand;
use Silex\Application;
use Silex\ServiceProviderInterface;
use CultuurNet\UDB3\Storage\DBALPurgeService;
use CultuurNet\UDB3\Storage\PurgeServiceManager;

class PurgeServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $application)
    {
        $application[PurgeModelCommand::class] = $application->share(
            function (Application $application) {
                return new PurgeModelCommand($this->createPurgeServiceManager($application));
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function boot(Application $app)
    {
    }

    private function createPurgeServiceManager(Application $application): PurgeServiceManager
    {
        $tablesToPurge = [
            'event_permission_readmodel',
            'event_relations',
            'labels_json',
            'label_roles',
            'labels_relations',
            'organizer_permission_readmodel',
            'place_permission_readmodel',
            'place_relations',
            'role_permissions',
            'roles_search_v3',
            'user_roles',
            'offer_metadata',
        ];

        return new PurgeServiceManager(
            array_map(function (string $table) use ($application) {
                return new DBALPurgeService($application['dbal_connection'], $table);
            }, $tablesToPurge)
        );
    }
}
