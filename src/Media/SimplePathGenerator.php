<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

final class SimplePathGenerator implements PathGeneratorInterface
{
    public function path(UUID $fileId, string $extension): string
    {
        return $fileId->toString() . '.' . $extension;
    }
}
