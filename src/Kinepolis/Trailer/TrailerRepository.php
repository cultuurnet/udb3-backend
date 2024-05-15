<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis\Trailer;

use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;

interface TrailerRepository
{
    public function search(string $title): ?Video;
}
