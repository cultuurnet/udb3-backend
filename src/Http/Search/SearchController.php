<?php

namespace CultuurNet\UDB3\Symfony\Search;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\Hydra\PagedCollection;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use CultuurNet\UDB3\Symfony\HttpFoundation\ApiProblemJsonResponse;
use CultuurNet\UDB3\Symfony\JsonLdResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchController
{
    /**
     * @var SearchServiceInterface
     */
    private $searchService;

    /**
     * @param SearchServiceInterface $searchService
     */
    public function __construct(SearchServiceInterface $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function search(Request $request)
    {
        $query = $request->query->get('query', '*.*');
        $limit = $request->query->getInt('limit', 30);
        $start = $request->query->getInt('start', 0);
        $sort  = $request->query->get('sort', 'lastupdated desc');

        try {
            $results = $this->searchService->search(
                $query,
                $limit,
                $start,
                $sort
            );

            $pagedCollection = new PagedCollection(
                $start / $limit,
                $limit,
                $results->getItems(),
                $results->getTotalItems()->toNative()
            );

            $response = JsonLdResponse::create()
                ->setData($pagedCollection)
                ->setPublic()
                ->setClientTtl(60 * 1)
                ->setTtl(60 * 5);

            return $response;
        } catch (\Exception $e) {
            $apiProblem = new ApiProblem(
                'An error occurred while searching. Please correct your search query.'
            );
            $apiProblem->setStatus(Response::HTTP_BAD_REQUEST);

            return new ApiProblemJsonResponse(
                $apiProblem
            );
        }
    }
}
