<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Label\Query;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Query;
use Psr\Http\Message\ServerRequestInterface;

interface QueryFactoryInterface
{
    public function createFromRequest(ServerRequestInterface $request): Query;
}
