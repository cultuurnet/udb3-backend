<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\SampleFiles;

trait EventSpecificationTestTrait
{
    protected function getEventLdFromFile(string $fileName): \stdClass
    {
        $jsonEvent = SampleFiles::read(
            __DIR__ . '/../../../samples/' . $fileName
        );

        return Json::decode($jsonEvent);
    }
}
