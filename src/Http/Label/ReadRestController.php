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
use ValueObjects\Exception\InvalidNativeArgumentException;
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

        $entities = $totalEntities->toNative() > 0 ? $this->readService->search($query) : [];

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
