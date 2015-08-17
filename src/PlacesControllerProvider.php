<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex;

use CultuurNet\Hydra\PagedCollection;
use CultuurNet\UDB3\EntityServiceInterface;
use CultuurNet\UDB3\Place\ReadModel\Lookup\PlaceLookupServiceInterface;
use CultuurNet\UDB3\Symfony\JsonLdResponse;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class PlacesControllerProvider implements ControllerProviderInterface
{
    /**
     * @inheritdoc
     */
    public function connect(Application $app)
    {
        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get(
            '/places',
            function (Application $app, Request $request) {
                /** @var PlaceLookupServiceInterface $placeLookupService */
                $placeLookupService = $app['place_lookup'];

                // @todo Add & process pagination parameters

                // @todo Validate zipcode
                $zipCode = $request->query->get('zipcode');

                $ids = $placeLookupService->findPlacesByPostalCode($zipCode);

                $members = [];
                if (!empty($ids)) {
                    /** @var EntityServiceInterface $placeService */
                    $placeService = $app['place_service'];

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
