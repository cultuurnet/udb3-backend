<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use Psr\Http\Message\UriInterface;

interface ReturnLastRequestedUri
{
    public function getLastRequestedUri(): ?UriInterface;
}
