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
            fn () => $this->buildUdb2EventCbidExtractor(),
        );
    }

    private function buildUdb2EventCbidExtractor(): EventCdbIdExtractor
    {
        return new EventCdbIdExtractor(
            $this->buildMappingServiceForLocation(__DIR__ . '../../config.external_id_mapping_place.php'),
            $this->buildMappingServiceForLocation(__DIR__ . '../../config.external_id_mapping_organizer.php'),
        );
    }

    private function buildMappingServiceForLocation(string $mappingFileLocation): ArrayMappingService
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
}
