<?php

namespace CultuurNet\UDB3\Silex;

use Doctrine\DBAL\Connection;
use Silex\Application;
use Silex\ServiceProviderInterface;
use CultuurNet\UDB3\Storage\DBALPurgeService;
use CultuurNet\UDB3\Storage\PurgeServiceManager;

/**
 * Class PurgeServiceProvider
 * @package CultuurNet\UDB3\Silex
 */
class PurgeServiceProvider implements ServiceProviderInterface
{
    const PURGE_SERVICE_MANAGER = 'purgeServiceManager';

    /**
     * @inheritdoc
     */
    public function register(Application $application)
    {
        $application[self::PURGE_SERVICE_MANAGER] = $application->share(
            function (Application $application) use ($application) {
                return $this->createPurgeServiceManager($application);
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function boot(Application $app)
    {

    }

    /**
     * @param Application $application
     * @return PurgeServiceManager
     */
    private function createPurgeServiceManager(Application $application)
    {
        $purgerServiceManager = new PurgeServiceManager();
        $connection = $application['dbal_connection'];

        $this->addReadModels($purgerServiceManager, $connection);
        $this->addWriteModels($purgerServiceManager, $connection);

        return $purgerServiceManager;
    }

    /**
     * @param PurgeServiceManager $purgeServiceManager
     * @param Connection $connection
     */
    private function addReadModels(PurgeServiceManager $purgeServiceManager, Connection $connection)
    {
        $purgeServiceManager->addReadModelPurgeService(
            new DBALPurgeService(
                $connection,
                'event_permission_readmodel'
            )
        );

        $purgeServiceManager->addReadModelPurgeService(
            new DBALPurgeService(
                $connection,
                'event_relations'
            )
        );

        $purgeServiceManager->addReadModelPurgeService(
            new DBALPurgeService(
                $connection,
                'event_variation_search_index'
            )
        );

        $purgeServiceManager->addReadModelPurgeService(
            new DBALPurgeService(
                $connection,
                'index_readmodel'
            )
        );
    }

    /**
     * @param PurgeServiceManager $purgeServiceManager
     * @param Connection $connection
     */
    private function addWriteModels(PurgeServiceManager $purgeServiceManager, Connection $connection)
    {
        $purgeServiceManager->addWriteModelPurgeService(
            new DBALPurgeService(
                $connection,
                'events'
            )
        );

        $purgeServiceManager->addWriteModelPurgeService(
            new DBALPurgeService(
                $connection,
                'media_objects'
            )
        );

        $purgeServiceManager->addWriteModelPurgeService(
            new DBALPurgeService(
                $connection,
                'organizers'
            )
        );

        $purgeServiceManager->addWriteModelPurgeService(
            new DBALPurgeService(
                $connection,
                'places'
            )
        );

        $purgeServiceManager->addWriteModelPurgeService(
            new DBALPurgeService(
                $connection,
                'variations'
            )
        );
    }
}