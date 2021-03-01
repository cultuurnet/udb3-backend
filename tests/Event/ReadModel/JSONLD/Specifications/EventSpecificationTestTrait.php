<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications;

trait EventSpecificationTestTrait
{
    protected function getEventLdFromFile($fileName)
    {
        $jsonEvent = file_get_contents(
            __DIR__ . '/../../../samples/' . $fileName
        );

        return json_decode($jsonEvent);
    }
}
