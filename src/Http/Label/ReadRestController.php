<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Label;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Response\PagedCollectionResponse;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Query;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Http\Label\Query\QueryFactoryInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class ReadRestController
{
    private ReadRepositoryInterface $labelRepository;

    private QueryFactoryInterface $queryFactory;

    public function __construct(
        ReadRepositoryInterface $readService,
        QueryFactoryInterface $queryFactory
    ) {
        $this->labelRepository = $readService;
        $this->queryFactory = $queryFactory;
    }

    public function get(string $id): JsonResponse
    {
        try {
            $entity = $this->labelRepository->getByUuid(new UUID($id));
        } catch (\InvalidArgumentException $exception) {
            $entity = $this->labelRepository->getByName($id);
        }

        if (!$entity) {
            throw ApiProblem::blank('There is no label with identifier: ' . $id, 404);
        }

        return new JsonResponse($entity);
    }

    public function search(Request $request): ResponseInterface
    {
        $query = $this->queryFactory->createFromRequest($request);

        $totalEntities = $this->labelRepository->searchTotalLabels($query);

        $entities = $totalEntities > 0 ? $this->labelRepository->search($query) : [];

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
