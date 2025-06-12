<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cultuurkuur;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use Slim\Psr7\Stream;

class GetRegionsRequestHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return (new Response())
            ->withBody(new Stream(fopen(__DIR__ . '/data/regions.json', 'rb')))
            ->withHeader('Content-Type', 'application/json');
    }
}
