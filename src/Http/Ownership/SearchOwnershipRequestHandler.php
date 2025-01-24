<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Ownership\Search\SearchParameter;
use CultuurNet\UDB3\Http\Ownership\Search\SearchQuery;
use CultuurNet\UDB3\Http\Response\PagedCollectionResponse;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\User\CurrentUser;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class SearchOwnershipRequestHandler implements RequestHandlerInterface
{
    private OwnershipSearchRepository $ownershipSearchRepository;
    private DocumentRepository $ownershipRepository;
    private CurrentUser $currentUser;
    private OwnershipStatusGuard $ownershipStatusGuard;

    public function __construct(
        OwnershipSearchRepository $ownershipSearchRepository,
        DocumentRepository $ownershipRepository,
        CurrentUser $currentUser,
        OwnershipStatusGuard $ownershipStatusGuard
    ) {
        $this->ownershipSearchRepository = $ownershipSearchRepository;
        $this->ownershipRepository = $ownershipRepository;
        $this->currentUser = $currentUser;
        $this->ownershipStatusGuard = $ownershipStatusGuard;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $searchParameters = [];
        foreach (SearchParameter::SUPPORTED_URL_PARAMETERS as $key) {
            $value = $request->getQueryParams()[$key] ?? '';
            if (!empty($value)) {
                $searchParameters[] = new SearchParameter($key, $value);
            }
        }

        if (count($searchParameters) === 0) {
            throw ApiProblem::queryParameterMissing('itemId or state');
        }

        $searchQuery = new SearchQuery(
            $searchParameters,
            !empty($request->getQueryParams()['start']) ? (int) $request->getQueryParams()['start'] : null,
            !empty($request->getQueryParams()['limit']) ? (int) $request->getQueryParams()['limit'] : null
        );

        $ownerships = [];
        // We need to keep track of the number of removed ownerships to calculate the total number of ownerships
        // Because the total count can't take into account the ownerships that are not allowed to be fetched
        $nrOfRemovedOwnerships = 0;
        $ownershipCollection = $this->ownershipSearchRepository->search($searchQuery);
        foreach ($ownershipCollection as $ownership) {
            try {
                $this->ownershipStatusGuard->isAllowedToGet($ownership->getId(), $this->currentUser);
            } catch (ApiProblem $apiProblem) {
                $nrOfRemovedOwnerships++;
                continue;
            }

            $ownerships[] = $this->ownershipRepository->fetch($ownership->getId())->getAssocBody();
        }

        return new PagedCollectionResponse(
            count($ownerships),
            $this->ownershipSearchRepository->searchTotal($searchQuery) - $nrOfRemovedOwnerships,
            $ownerships
        );
    }
}
