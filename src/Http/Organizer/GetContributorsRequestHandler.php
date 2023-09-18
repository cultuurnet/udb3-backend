<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\Repository\AggregateNotFoundException;
use CultuurNet\UDB3\Contributor\ContributorRepository;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Organizer\OrganizerRepository;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetContributorsRequestHandler implements RequestHandlerInterface
{
    private OrganizerRepository $organizerRepository;

    private ContributorRepository $contributorRepository;

    private PermissionVoter $permissionVoter;

    private ?string $currentUserId;

    public function __construct(
        OrganizerRepository $organizerRepository,
        ContributorRepository $contributorRepository,
        PermissionVoter $permissionVoter,
        ?string $currentUserId
    ) {
        $this->organizerRepository = $organizerRepository;
        $this->contributorRepository = $contributorRepository;
        $this->permissionVoter = $permissionVoter;
        $this->currentUserId = $currentUserId;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $organizerId = $routeParameters->getOrganizerId();

        try {
            $this->organizerRepository->load($organizerId);
        } catch (AggregateNotFoundException $exception) {
            throw ApiProblem::organizerNotFound($organizerId);
        }

        if (
            !$this->permissionVoter->isAllowed(
                Permission::aanbodBewerken(),
                $organizerId,
                $this->currentUserId
            )
        ) {
            throw ApiProblem::forbidden(
                sprintf(
                    'User %s has no permission "%s" on resource %s',
                    $this->currentUserId,
                    Permission::aanbodBewerken()->toString(),
                    $organizerId
                )
            );
        }

        $results = $this->contributorRepository->getContributors(new UUID($organizerId))->toArray();

        return new JsonResponse(
            array_map(
                fn (EmailAddress $email) => $email->toString(),
                $results
            )
        );
    }
}
