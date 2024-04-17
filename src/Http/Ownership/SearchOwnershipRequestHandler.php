<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Ownership\Search\SearchParameter;
use CultuurNet\UDB3\Http\Ownership\Search\SearchQuery;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class SearchOwnershipRequestHandler implements RequestHandlerInterface
{
    private OwnershipSearchRepository $ownershipSearchRepository;
    private DocumentRepository $ownershipRepository;

    public function __construct(
        OwnershipSearchRepository $ownershipSearchRepository,
        DocumentRepository $ownershipRepository
    ) {
        $this->ownershipSearchRepository = $ownershipSearchRepository;
        $this->ownershipRepository = $ownershipRepository;
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

        $ownerships = [];
        $ownershipCollection = $this->ownershipSearchRepository->search(
            new SearchQuery(
                $searchParameters,
                !empty($request->getQueryParams()['offset']) ? (int) $request->getQueryParams()['offset'] : 0,
                !empty($request->getQueryParams()['limit']) ? (int) $request->getQueryParams()['limit'] : 50
            )
        );
        foreach ($ownershipCollection as $ownership) {
            $ownerships[] = $this->ownershipRepository->fetch($ownership->getId())->getAssocBody();
        }

        return new JsonLdResponse(
            $ownerships,
            StatusCodeInterface::STATUS_OK
        );
    }
}
