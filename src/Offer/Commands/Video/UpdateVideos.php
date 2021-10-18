<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Video;

use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

final class UpdateVideos extends Collection
{
    public function __construct(UpdateVideo ...$updateVideos)
    {
        parent::__construct(...$updateVideos);
    }
}
