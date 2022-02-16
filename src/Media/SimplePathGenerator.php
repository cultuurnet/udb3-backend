<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\StringLiteral;

class SimplePathGenerator implements PathGeneratorInterface
{
    /**
     * @{inheritdoc}
     */
    public function path(UUID $fileId, StringLiteral $extension)
    {
        return $fileId->toString() . '.' . (string)$extension;
    }
}
