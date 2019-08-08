<?php

namespace CultuurNet\UDB3\Http\Label;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\Hydra\PagedCollection;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Query;
use CultuurNet\UDB3\Label\Services\ReadServiceInterface;
use CultuurNet\UDB3\Http\HttpFoundation\ApiProblemJsonResponse;
use CultuurNet\UDB3\Http\Label\Query\QueryFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ValueObjects\Exception\InvalidNativeArgumentException;
use ValueObjects\Identity\UUID;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;

class ReadRestController
{
    /**
     * @var ReadServiceInterface
     */
    private $readService;

    /**
     * @var QueryFactoryInterface
     */
    private $queryFactory;

    /**
     * ReadRestController constructor.
     * @param ReadServiceInterface $readService
     * @param QueryFactoryInterface $queryFactory
     */
    public function __construct(
        ReadServiceInterface $readService,
        QueryFactoryInterface $queryFactory
    ) {
        $this->readService = $readService;
        $this->queryFactory = $queryFactory;
    }

    /**
     * @param string $id
     *  The uuid or unique name of a label.
     * @return JsonResponse
     */
    public function get($id)
    {
        try {
            $entity = $this->readService->getByUuid(new UUID($id));
        } catch (InvalidNativeArgumentException $exception) {
            $entity = $this->readService->getByName(new StringLiteral($id));
        }

        if ($entity) {
            return new JsonResponse($entity);
        } else {
            $apiProblem = new ApiProblem('There is no label with identifier: ' . $id);
            $apiProblem->setStatus(Response::HTTP_NOT_FOUND);

            return new ApiProblemJsonResponse($apiProblem);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request)
    {
        $query = $this->queryFactory->createFromRequest($request);

        $totalEntities = $this->readService->searchTotalLabels($query);

        if ($totalEntities) {
            $entities = $this->readService->search($query);

            $pagedCollection = $this->createPagedCollection(
                $query,
                $entities !== null ? $entities : [],
                $totalEntities
            );
            return new JsonResponse($pagedCollection);
        } else {
            $apiProblem = new ApiProblem('No label found for search query.');
            $apiProblem->setStatus(Response::HTTP_NOT_FOUND);

            return new ApiProblemJsonResponse($apiProblem);
        }
    }

    /**
     * @param Query $query
     * @param Entity[] $entities
     * @param Natural $totalEntities
     * @return PagedCollection
     */
    private function createPagedCollection(
        Query $query,
        array $entities,
        Natural $totalEntities
    ) {
        $pageNumber = 0;
        $limit = 0;

        if ($query->getOffset() && $query->getLimit()) {
            $pageNumber = (int) ($query->getOffset()->toNative() /
                $query->getLimit()->toNative());
        }

        if ($query->getLimit()) {
            $limit = $query->getLimit()->toNative();
        }

        return new PagedCollection(
            $pageNumber,
            $limit,
            $entities,
            $totalEntities->toNative()
        );
    }
}
