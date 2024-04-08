<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
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
        $itemId = $request->getQueryParams()['itemId'] ?? '';

        if (empty($itemId)) {
            throw ApiProblem::queryParameterMissing('itemId');
        }

        $ownerships = [];
        $ownershipCollection = $this->ownershipSearchRepository->getByItemId($itemId);
        foreach ($ownershipCollection as $ownership) {
            $ownerships[] = $this->ownershipRepository->fetch($ownership->getId())->getAssocBody();
        }

        return new JsonLdResponse(
            $ownerships,
            StatusCodeInterface::STATUS_OK
        );
    }
}
