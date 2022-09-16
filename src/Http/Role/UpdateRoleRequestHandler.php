<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use CultuurNet\UDB3\Role\MissingContentTypeException;
use CultuurNet\UDB3\Role\UnknownContentTypeException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class UpdateRoleRequestHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->ensureContentTypeIsProvided($request);
    }

    private function ensureContentTypeIsProvided(ServerRequestInterface $request): void
    {
        if (!$request->hasHeader('Content-Type')) {
            throw new MissingContentTypeException();
        }

        $contentType = $request->getHeader('Content-Type');
        if ($contentType !== 'application/ld+json;domain-model=RenameRole') {
            throw new UnknownContentTypeException();
        }
    }
}
