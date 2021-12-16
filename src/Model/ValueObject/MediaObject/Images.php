<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

final class Images extends Collection
{
    public function __construct(Image ...$images)
    {
        parent::__construct(...$images);
    }
}
