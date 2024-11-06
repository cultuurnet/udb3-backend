<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
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
    private UserIdentityResolver $userIdentityResolver;
    private OwnershipStatusGuard $ownershipStatusGuard;

    public function __construct(DocumentRepository $organizerRepository, UserIdentityResolver $userIdentityResolver, OwnershipStatusGuard $ownershipStatusGuard, CurrentUser $currentUser)
    {
        $this->organizerRepository = $organizerRepository;
        $this->userIdentityResolver = $userIdentityResolver;
        $this->ownershipStatusGuard = $ownershipStatusGuard;
        $this->currentUser = $currentUser;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {

        $routeParameters = new RouteParameters($request);
        $ownershipId = $routeParameters->getOwnershipId();

        $this->ownershipStatusGuard->isAllowedToGetCreator($ownershipId, $this->currentUser);

        try {
            $organisation = $this->organizerRepository->fetch($ownershipId);
            $creatorId = $organisation->getBody()->creator;
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
            throw ApiProblem::resourceNotFound('Organizer', $ownershipId);
        }
    }
}
