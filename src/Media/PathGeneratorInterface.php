<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

interface PathGeneratorInterface
{
    public function path(UUID $fileId, string $extension): string;
}
