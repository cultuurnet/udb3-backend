<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

interface PathGeneratorInterface
{
    /**
     * Returns the path where a file is stored
     *
     *
     * @return string
     */
    public function path(UUID $fileId, string $extension);
}
