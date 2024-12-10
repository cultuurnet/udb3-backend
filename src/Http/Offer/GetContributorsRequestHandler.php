<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\Repository\AggregateNotFoundException;
use CultuurNet\UDB3\Contributor\ContributorRepository;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetContributorsRequestHandler implements RequestHandlerInterface
{
    private OfferRepository $offerRepository;

    private ContributorRepository $contributorRepository;

    private PermissionVoter $permissionVoter;

    private ?string $currentUserId;

    public function __construct(
        OfferRepository $offerRepository,
        ContributorRepository $contributorRepository,
        PermissionVoter $permissionVoter,
        ?string $currentUserId
    ) {
        $this->offerRepository = $offerRepository;
        $this->contributorRepository = $contributorRepository;
        $this->permissionVoter = $permissionVoter;
        $this->currentUserId = $currentUserId;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerType = $routeParameters->getOfferType();
        $offerId = $routeParameters->getOfferId();

        try {
            $this->offerRepository->load($offerId);
        } catch (AggregateNotFoundException $exception) {
            throw ApiProblem::offerNotFound($offerType, $offerId);
        }

        if (
            !$this->permissionVoter->isAllowed(
                Permission::aanbodBewerken(),
                $offerId,
                $this->currentUserId
            )
        ) {
            throw ApiProblem::forbidden(
                sprintf(
                    'User %s has no permission "%s" on resource %s',
                    $this->currentUserId,
                    Permission::aanbodBewerken()->toString(),
                    $offerId
                )
            );
        }

        $results = $this->contributorRepository->getContributors(new Uuid($offerId))->toArray();

        return new JsonResponse(
            array_map(
                fn (EmailAddress $email) => $email->toString(),
                $results
            )
        );
    }
}
