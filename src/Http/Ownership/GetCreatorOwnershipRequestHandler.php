<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\User\UserIdentityResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetCreatorOwnershipRequestHandler implements RequestHandlerInterface
{
    private DocumentRepository $organizerRepository;
    private UserIdentityResolver $userIdentityResolver;

    public function __construct(DocumentRepository $organizerRepository, UserIdentityResolver $userIdentityResolver)
    {
        $this->organizerRepository = $organizerRepository;
        $this->userIdentityResolver = $userIdentityResolver;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {

        // TODO: Add guard logic

        $routeParameters = new RouteParameters($request);
        $ownershipId = $routeParameters->getOwnershipId();

        try {
            $organisation = $this->organizerRepository->fetch($ownershipId);
            $creatorId = $organisation->getBody()->creator;
            $creator = $this->userIdentityResolver->getUserById($creatorId);

            if ($creator === null) {
                throw ApiProblem::resourceNotFound('creator', $creatorId);
            }

            return new JsonLdResponse(
                [
                    'userId' => $creator->getUserId(),
                    'email' => $creator->getEmailAddress(),
                ]
            );
        } catch (DocumentDoesNotExist $exception) {
            throw ApiProblem::ownershipNotFound($ownershipId);
        }
    }
}
