<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

interface PathGeneratorInterface
{
    public function path(Uuid $fileId, string $extension): string;
}
