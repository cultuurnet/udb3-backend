<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

final class UpdateImages extends Collection
{
    public function __construct(UpdateImage ...$updateImage)
    {
        parent::__construct(...$updateImage);
    }
}
