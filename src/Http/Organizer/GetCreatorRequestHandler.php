<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use CultuurNet\UDB3\User\CurrentUser;
use CultuurNet\UDB3\User\UserIdentityResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetCreatorRequestHandler implements RequestHandlerInterface
{
    private DocumentRepository $organizerRepository;
    private UserIdentityResolver $userIdentityResolver;
    private PermissionVoter $permissionVoter;
    private CurrentUser $currentUser;

    public function __construct(DocumentRepository $organizerRepository, UserIdentityResolver $userIdentityResolver, PermissionVoter $permissionVoter, CurrentUser $currentUser)
    {
        $this->organizerRepository = $organizerRepository;
        $this->userIdentityResolver = $userIdentityResolver;
        $this->permissionVoter = $permissionVoter;
        $this->currentUser = $currentUser;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $routeParameters = new RouteParameters($request);
            $organizerId = $routeParameters->getOrganizerId();

            $isAllowed = $this->permissionVoter->isAllowed(
                Permission::organisatiesBewerken(),
                $organizerId,
                $this->currentUser->getId()
            );

            if (!$isAllowed) {
                throw ApiProblem::forbidden('You are not allowed to get creator for this item');
            }

            $organizer = $this->organizerRepository->fetch($organizerId);

            if (!property_exists($organizer->getBody(), 'creator')) {
                throw ApiProblem::resourceNotFound('Creator', 'unknown');
            }

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
