<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Label;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Response\PagedCollectionResponse;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Query;
use CultuurNet\UDB3\Label\Services\ReadServiceInterface;
use CultuurNet\UDB3\HttpFoundation\Response\ApiProblemJsonResponse;
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
     * @return JsonResponse
     */
    public function search(Request $request)
    {
        $query = $this->queryFactory->createFromRequest($request);

        $totalEntities = $this->readService->searchTotalLabels($query);

        if ($totalEntities->toInteger() > 0) {
            $entities = $this->readService->search($query);

            return $this->createPagedCollectionResponse(
                $query,
                $entities !== null ? $entities : [],
                $totalEntities
            );
        } else {
            $apiProblem = new ApiProblem('No label found for search query.');
            $apiProblem->setStatus(Response::HTTP_NOT_FOUND);

            return new ApiProblemJsonResponse($apiProblem);
        }
    }

    /**
     * @param Entity[] $entities
     */
    private function createPagedCollectionResponse(
        Query $query,
        array $entities,
        Natural $totalEntities
    ): PagedCollectionResponse {
        $limit = 0;

        if ($query->getLimit()) {
            $limit = $query->getLimit()->toNative();
        }

        return new PagedCollectionResponse(
            $limit,
            $totalEntities->toNative(),
            $entities
        );
    }
}
