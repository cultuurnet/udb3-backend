<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Role\Commands\RenameRole;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class UpdateRoleRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->ensureContentTypeIsProvided($request);

        $routeParameters = new RouteParameters($request);
        $roleId = $routeParameters->getRoleId();

        $body = Json::decodeAssociatively($request->getBody()->getContents());

        $this->commandBus->dispatch(new RenameRole($roleId, (string) $body['name']));

        return new Response(StatusCodeInterface::STATUS_NO_CONTENT);
    }

    private function ensureContentTypeIsProvided(ServerRequestInterface $request): void
    {
        if (!$request->hasHeader('Content-Type')) {
            throw ApiProblem::unsupportedMediaType();
        }

        $contentType = $request->getHeader('Content-Type')[0];
        if ($contentType != 'application/ld+json;domain-model=RenameRole') {
            throw ApiProblem::unsupportedMediaType();
        }
    }
}
