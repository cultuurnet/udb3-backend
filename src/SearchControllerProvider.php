<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Hydra\PagedCollection;
use CultuurNet\UDB3\Silex\HttpFoundation\ApiProblemJsonResponse;
use CultuurNet\UDB3\Symfony\JsonLdResponse;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class SearchControllerProvider implements ControllerProviderInterface
{
    /**
     * @inheritdoc
     */
    public function connect(Application $app)
    {
        /* @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get(
            'api/1.0/search',
            function (Request $request, Application $app) {
                $query = $request->query->get('query', '*.*');
                $limit = $request->query->getInt('limit', 30);
                $start = $request->query->getInt('start', 0);
                $sort  = $request->query->get('sort', 'lastupdated desc');

                /** @var \CultuurNet\UDB3\Search\SearchServiceInterface $searchService */
                $searchService = $app['cached_search_service'];
                try {
                    $results = $searchService->search(
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
                }
                catch (\Exception $e) {
                    return new ApiProblemJsonResponse(
                        new ApiProblem(
                            'An error occurred while searching. Please correct your search query.'
                        ),
                        400
                    );
                }
            }
        )->bind('api/1.0/search');

        return $controllers;
    }

}
