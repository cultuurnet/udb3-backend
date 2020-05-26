<?php

namespace CultuurNet\UDB3\Media;

use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

interface PathGeneratorInterface
{
    /**
     * Returns the path where a file is stored
     *
     * @param UUID $fileId
     * @param StringLiteral $extension
     *
     * @return string
     */
    public function path(UUID $fileId, StringLiteral $extension);
}
