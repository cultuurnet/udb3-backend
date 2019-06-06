<?php

namespace CultuurNet\UDB3\EventExport\Media;

use stdClass;

interface MediaSpecificationInterface
{
    /**
     * @param stdClass $mediaObject
     * @return bool
     */
    public function matches($mediaObject);
}
