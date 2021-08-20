<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface RequestHandler
{
    public function handle(ServerRequestInterface $request): ResponseInterface;
}
