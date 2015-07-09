<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex;

use CultuurNet\Search\Parameter\FilterQuery;
use CultuurNet\Search\Parameter\Query;
use CultuurNet\UDB3\EntityServiceInterface;
use CultuurNet\UDB3\Hydra\PagedCollection;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use CultuurNet\UDB3\Symfony\JsonLdResponse;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DummyControllerProvider implements ControllerProviderInterface
{
    /**
     * @inheritdoc
     */
    public function connect(Application $app)
    {
        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get(
            '/api/1.0/city/suggest/{city}',
            function ($city) {
                return (new JsonResponse())->setContent(
                '[{"cid":"3000_LEUVEN","name":"Leuven","zip":"3000","cityId":"3000_Leuven","cityLabel":"3000 Leuven"}]');
            }
        );

        $controllers->get(
            '/api/1.0/location/suggest/{query}/{postalCode}',
            function ($query, $postalCode) {
                if ($postalCode == '3000' && $query == "Dep") {
                    return (new JsonResponse())->setContent(
                        '[{"id":"22db6f6e-a944-4ecc-a002-74f216294f45","title":"Het Depot"}]'
                    );
                }
                else {
                    return (new JsonResponse())->setContent('[]');
                }
            }
        );

        $controllers->get(
            '/places',
            function (Application $app, Request $request) {
                $members = [];

                /** @var EntityServiceInterface $placeService */
                $placeService = $app['place_service'];
                /** @var SearchServiceInterface $searchService */
                $searchService = $app['place_search_service'];

                $query = $request->query->get('q', '*.*');

                $searchResult = $searchService->search($query, 1000);

                $members = array_map(
                    function ($searchResult) use ($placeService) {
                        $uri = $searchResult['@id'];
                        $uriParts = explode('/', $uri);
                        $id = array_pop($uriParts);
                        $place = json_decode($placeService->getEntity($id));
                        return $place;
                    },
                    $searchResult->getItems()
                );

                $pagedCollection = new PagedCollection(
                    1,
                    1000,
                    $members,
                    $searchResult->getTotalItems()->toNative()
                );

                return (new JsonLdResponse($pagedCollection));
            }
        );

        return $controllers;
    }

}
