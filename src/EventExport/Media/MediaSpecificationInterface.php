<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Media;

use stdClass;

interface MediaSpecificationInterface
{
    public function matches(stdClass $mediaObject): bool;
}
