<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

class VideoCollection extends Collection
{

    public function __construct(Video ...$videos)
    {
        parent::__construct(...$videos);
    }
}
