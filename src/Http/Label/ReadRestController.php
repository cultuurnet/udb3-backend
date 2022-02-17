<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Label;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Response\PagedCollectionResponse;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Query;
use CultuurNet\UDB3\Label\Services\ReadServiceInterface;
use CultuurNet\UDB3\Http\Label\Query\QueryFactoryInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use CultuurNet\UDB3\StringLiteral;

final class ReadRestController
{
    private ReadServiceInterface $readService;

    private QueryFactoryInterface $queryFactory;

    public function __construct(
        ReadServiceInterface $readService,
        QueryFactoryInterface $queryFactory
    ) {
        $this->readService = $readService;
        $this->queryFactory = $queryFactory;
    }

    public function get(string $id): JsonResponse
    {
        try {
            $entity = $this->readService->getByUuid(new UUID($id));
        } catch (\InvalidArgumentException $exception) {
            $entity = $this->readService->getByName(new StringLiteral($id));
        }

        if (!$entity) {
            throw ApiProblem::blank('There is no label with identifier: ' . $id, 404);
        }

        return new JsonResponse($entity);
    }

    public function search(Request $request): ResponseInterface
    {
        $query = $this->queryFactory->createFromRequest($request);

        $totalEntities = $this->readService->searchTotalLabels($query);

        $entities = $totalEntities > 0 ? $this->readService->search($query) : [];

        return $this->createPagedCollectionResponse(
            $query,
            $entities,
            $totalEntities
        );
    }

    /**
     * @param Entity[] $entities
     */
    private function createPagedCollectionResponse(
        Query $query,
        array $entities,
        int $totalEntities
    ): PagedCollectionResponse {
        $limit = $query->getLimit() ?? 0;

        return new PagedCollectionResponse(
            $limit,
            $totalEntities,
            $entities
        );
    }
}
