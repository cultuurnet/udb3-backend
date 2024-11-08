<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\User\CurrentUser;
use CultuurNet\UDB3\User\UserIdentityResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetCreatorOwnershipRequestHandler implements RequestHandlerInterface
{
    private CurrentUser $currentUser;
    private DocumentRepository $organizerRepository;
    private OwnershipSearchRepository $ownershipSearchRepository;
    private UserIdentityResolver $userIdentityResolver;
    private OwnershipStatusGuard $ownershipStatusGuard;

    public function __construct(DocumentRepository $organizerRepository, UserIdentityResolver $userIdentityResolver, OwnershipStatusGuard $ownershipStatusGuard, CurrentUser $currentUser, OwnershipSearchRepository $ownershipSearchRepository)
    {
        $this->organizerRepository = $organizerRepository;
        $this->userIdentityResolver = $userIdentityResolver;
        $this->ownershipStatusGuard = $ownershipStatusGuard;
        $this->currentUser = $currentUser;
        $this->ownershipSearchRepository = $ownershipSearchRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $routeParameters = new RouteParameters($request);
            $ownershipId = $routeParameters->getOwnershipId();
            $ownership = $this->ownershipSearchRepository->getById($ownershipId);

            $organizerId = $ownership->getItemId();
            $organizer = $this->organizerRepository->fetch($organizerId);

            $this->ownershipStatusGuard->isAllowedToGetCreator($organizerId, $this->currentUser);

            $creatorId = $organizer->getBody()->creator;
            $creator = $this->userIdentityResolver->getUserById($creatorId);

            if ($creator === null) {
                throw ApiProblem::resourceNotFound('Creator', $creatorId);
            }

            return new JsonLdResponse(
                [
                    'userId' => $creator->getUserId(),
                    'email' => $creator->getEmailAddress(),
                ]
            );
        } catch (DocumentDoesNotExist $exception) {
            throw ApiProblem::resourceNotFound('Organizer', $organizerId);
        }
    }
}
