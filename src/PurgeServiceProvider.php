<?php

namespace CultuurNet\UDB3\Silex;

use Doctrine\DBAL\Connection;
use Silex\Application;
use Silex\ServiceProviderInterface;
use CultuurNet\UDB3\Storage\DBALPurgeService;
use CultuurNet\UDB3\Storage\PurgeServiceManager;

/**
 * Class PurgeServiceProvider
 *
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
     * @param Connection          $connection
     */
    private function addReadModels(PurgeServiceManager $purgeServiceManager, Connection $connection)
    {
        $dbalReadModels = [
            'event_permission_readmodel',
            'event_relations',
            'place_relations',
            'event_variation_search_index',
            'index_readmodel',
            'place_permission_readmodel',
        ];

        foreach ($dbalReadModels as $dbalReadModel) {
            $purgeServiceManager->addReadModelPurgeService(
                new DBALPurgeService(
                    $connection,
                    $dbalReadModel
                )
            );
        }
    }

    /**
     * @param PurgeServiceManager $purgeServiceManager
     * @param Connection          $connection
     */
    private function addWriteModels(PurgeServiceManager $purgeServiceManager, Connection $connection)
    {
        $dbalWriteModels = [
            'events',
            'media_objects',
            'organizers',
            'places',
            'variations',
        ];

        foreach ($dbalWriteModels as $dbalWriteModel) {
            $purgeServiceManager->addWriteModelPurgeService(
                new DBALPurgeService(
                    $connection,
                    $dbalWriteModel
                )
            );
        }
    }
}
