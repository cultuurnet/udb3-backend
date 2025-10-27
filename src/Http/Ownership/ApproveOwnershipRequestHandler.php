<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Organizer\Commands\ChangeOwner;
use CultuurNet\UDB3\Ownership\Commands\ApproveOwnership;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\User\CurrentUser;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ApproveOwnershipRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    private CurrentUser $currentUser;

    private OwnershipStatusGuard $ownershipStatusGuard;

    private OwnershipSearchRepository $ownershipSearchRepository;

    private DocumentRepository $organizerRepository;

    public function __construct(
        CommandBus $commandBus,
        CurrentUser $currentUser,
        OwnershipStatusGuard $ownershipStatusGuard,
        OwnershipSearchRepository $ownershipSearchRepository,
        DocumentRepository $organizerRepository
    ) {
        $this->commandBus = $commandBus;
        $this->currentUser = $currentUser;
        $this->ownershipStatusGuard = $ownershipStatusGuard;
        $this->ownershipSearchRepository = $ownershipSearchRepository;
        $this->organizerRepository = $organizerRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $ownershipId = $routeParameters->getOwnershipId();

        $this->ownershipStatusGuard->isAllowedToApprove($ownershipId, $this->currentUser);

        $this->commandBus->dispatch(
            new ApproveOwnership(new Uuid($ownershipId))
        );

        $ownership = $this->ownershipSearchRepository->getById($ownershipId);

        if (!$this->itemHasCreator($ownership->getItemId())) {
            $this->commandBus->dispatch(
                new ChangeOwner($ownership->getItemId(), $ownership->getOwnerId())
            );
        }

        return new JsonResponse(
            [],
            StatusCodeInterface::STATUS_NO_CONTENT
        );
    }

    private function itemHasCreator(string $organizerId): bool
    {
        $document = $this->organizerRepository->fetch($organizerId);
        $jsonLd = Json::decodeAssociatively($document->getRawBody());
        return $jsonLd->creator !== null;
    }
}
