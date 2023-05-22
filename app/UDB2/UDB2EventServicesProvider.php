<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2;

use CultuurNet\UDB3\Cdb\CdbId\EventCdbIdExtractor;
use CultuurNet\UDB3\Cdb\ExternalId\ArrayMappingService;
use CultuurNet\UDB3\Container\AbstractServiceProvider;

final class UDB2EventServicesProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'udb2_event_cdbid_extractor',
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'udb2_event_cdbid_extractor',
            fn () => new EventCdbIdExtractor(
                $this->buildMappingServiceForPlaces(),
                $this->buildMappingServiceForOrganizers(),
            ),
        );
    }

    private static function buildMappingService(string $mappingFileLocation): ArrayMappingService
    {
        $map = [];

        if (file_exists($mappingFileLocation)) {
            $mapping = require $mappingFileLocation;

            if (is_array($mapping)) {
                $map = $mapping;
            }
        }

        return new ArrayMappingService($map);
    }

    public static function buildMappingServiceForPlaces(): ArrayMappingService
    {
        return self::buildMappingService(__DIR__ . '../../config.external_id_mapping_place.php');
    }

    public static function buildMappingServiceForOrganizers(): ArrayMappingService
    {
        return self::buildMappingService(__DIR__ . '../../config.external_id_mapping_organizer.php');
    }
}
