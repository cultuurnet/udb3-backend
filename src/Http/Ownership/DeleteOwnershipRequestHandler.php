<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Ownership\Commands\DeleteOwnership;
use CultuurNet\UDB3\User\CurrentUser;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class DeleteOwnershipRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;
    private CurrentUser $currentUser;
    private OwnershipStatusGuard $ownershipStatusGuard;

    public function __construct(
        CommandBus $commandBus,
        CurrentUser $currentUser,
        OwnershipStatusGuard $ownershipStatusGuard
    ) {
        $this->commandBus = $commandBus;
        $this->currentUser = $currentUser;
        $this->ownershipStatusGuard = $ownershipStatusGuard;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $ownershipId = $routeParameters->getOwnershipId();

        $this->ownershipStatusGuard->isAllowedToDelete($ownershipId, $this->currentUser);

        $this->commandBus->dispatch(
            new DeleteOwnership(new Uuid($ownershipId))
        );

        return new JsonResponse(
            [],
            StatusCodeInterface::STATUS_NO_CONTENT
        );
    }
}
