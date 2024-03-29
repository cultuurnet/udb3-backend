<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Role\Commands\AddLabel;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AddLabelToRoleRequestHandler implements RequestHandlerInterface
{
    use GetLabelIdFromRouteParameters;

    private CommandBus $commandBus;

    private ReadRepositoryInterface $labelRepository;

    public function __construct(CommandBus $commandBus, ReadRepositoryInterface $labelRepository)
    {
        $this->commandBus = $commandBus;
        $this->labelRepository = $labelRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $roleId = $routeParameters->getRoleId();

        $labelId = $this->getLabelId($routeParameters);

        $this->commandBus->dispatch(new AddLabel($roleId, $labelId));

        return new NoContentResponse();
    }
}
