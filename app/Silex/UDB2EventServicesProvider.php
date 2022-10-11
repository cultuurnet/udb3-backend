<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\Cdb\CdbId\EventCdbIdExtractor;
use CultuurNet\UDB3\Cdb\ExternalId\ArrayMappingService;
use Silex\Application;
use Silex\ServiceProviderInterface;

class UDB2EventServicesProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app['udb2_event_cdbid_extractor'] = $app->share(
            function (Application $app) {
                return new EventCdbIdExtractor(
                    $app['udb2_place_external_id_mapping_service'],
                    $app['udb2_organizer_external_id_mapping_service']
                );
            }
        );

        $app['udb2_place_external_id_mapping_service'] = $app->share(
            function (Application $app) {
                return $app['udb2_external_id_mapping_service_factory'](__DIR__ . '../../config.external_id_mapping_place.php');
            }
        );

        $app['udb2_organizer_external_id_mapping_service'] = $app->share(
            function (Application $app) {
                return $app['udb2_external_id_mapping_service_factory'](__DIR__ . '../../config.external_id_mapping_organizer.php');
            }
        );

        $app['udb2_external_id_mapping_service_factory'] = $app->protect(
            function ($mappingFileLocation) {
                $map = [];

                if (file_exists($mappingFileLocation)) {
                    $mapping = require $mappingFileLocation;

                    if (is_array($mapping)) {
                        $map = $mapping;
                    }
                }

                return new ArrayMappingService($map);
            }
        );
    }

    public function boot(Application $app): void
    {
    }
}
