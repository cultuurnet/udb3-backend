<?php

namespace CultuurNet\UDB3\Media;

use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class SimplePathGenerator implements PathGeneratorInterface
{
    /**
     * @{inheritdoc}
     */
    public function path(UUID $fileId, StringLiteral $extension)
    {
        return (string)$fileId . '.' . (string)$extension;
    }
}
