<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\EntityServiceInterface;
use CultuurNet\Hydra\PagedCollection;
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

                if ($request->query->get('q') === 'zipcode:3000') {
                    $ids = [
                        '7540A176-F9DE-A04E-D0592C7E3006528C',
                        '56AF6D44-0DDA-76D4-2F5EE4184024FD78',
                        '429A87B3-E3B7-697C-5C94A5159389EF25',
                        '5023e3af-3fe1-45be-8a72-86ebe9ffa2fe',
                    ];

                    $members = array_map(
                        function ($id) use ($placeService) {
                            return json_decode($placeService->getEntity($id));
                        },
                        $ids
                    );
                }

                $pagedCollection = new PagedCollection(
                    1,
                    1000,
                    $members,
                    count($members)
                );

                return (new JsonLdResponse($pagedCollection));
            }
        );

        return $controllers;
    }

}
