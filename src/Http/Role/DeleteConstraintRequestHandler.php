<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\Commands\RemoveConstraint;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class DeleteConstraintRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $roleId = new UUID($routeParameters->getRoleId());

        $this->commandBus->dispatch(
            new RemoveConstraint(
                $roleId
            )
        );

        return new Response(StatusCodeInterface::STATUS_NO_CONTENT);
    }
}
