<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\Repository\AggregateNotFoundException;
use CultuurNet\UDB3\Contributor\ContributorRepositoryInterface;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Organizer\OrganizerRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetContributorsRequestHandler implements RequestHandlerInterface
{
    private OrganizerRepository $organizerRepository;

    private ContributorRepositoryInterface $contributorRepository;

    public function __construct(
        OrganizerRepository $organizerRepository,
        ContributorRepositoryInterface $contributorRepository
    ) {
        $this->organizerRepository = $organizerRepository;
        $this->contributorRepository = $contributorRepository;
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

        $results = $this->contributorRepository->getContributors(new UUID($organizerId))->toArray();

        return new JsonResponse(
            array_map(
                fn (EmailAddress $email) => $email->toString(),
                $results
            )
        );
    }
}
