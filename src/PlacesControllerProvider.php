<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex;

use CultuurNet\Hydra\PagedCollection;
use CultuurNet\UDB3\EntityServiceInterface;
use CultuurNet\UDB3\Place\ReadModel\Lookup\PlaceLookupServiceInterface;
use CultuurNet\UDB3\Symfony\JsonLdResponse;
use CultuurNet\UDB3\Symfony\PlaceRestController;
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
        $app['places_controller'] = $app->share(
            function (Application $app) {
                return new PlaceRestController(
                    $app['place_service'],
                    $app['place_editing_service'],
                    $app['event_relations_repository'],
                    $app['current_user']
                );
            }
        );

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

        // @todo Reduce path to /place.
        $controllers->post('api/1.0/place', 'places_controller:createPlace');
        $controllers->post('api/1.0/place/{cdbid}/image', 'places_controller:addImage');

        $controllers->post('place/{cdbid}/nl/description', 'places_controller:updateDescription');
        $controllers->post('place/{cdbid}/typicalAgeRange', 'places_controller:updateTypicalAgeRange');
        $controllers->delete('api/1.0/place/{cdbid}/typicalAgeRange', 'places_controller:deleteTypicalAgeRange');
        $controllers->post('place/{cdbid}/major-info', 'places_controller:updateMajorInfo');
        $controllers->post('place/{cdbid}/bookingInfo', 'places_controller:updateBookingInfo');
        $controllers->post('place/{cdbid}/contactPoint', 'places_controller:updateContactPoint');
        $controllers->post('place/{cdbid}/facilities', 'places_controller:updateFacilities');
        $controllers->post('place/{cdbid}/organizer', 'places_controller:updateOrganizer');
        $controllers->delete('place/{cdbid}/organizer/{organizerId}', 'places_controller:deleteOrganizer');

        $controllers->get(
            'place/{cdbid}',
            function (Application $app, $cdbid) {
                /** @var \CultuurNet\UDB3\EntityServiceInterface $service */
                $service = $app['place_service'];

                $place = $service->getEntity($cdbid);

                $response = JsonLdResponse::create()
                    ->setContent($place)
                    ->setPublic()
                    ->setClientTtl(60 * 30)
                    ->setTtl(60 * 5);

                $response->headers->set('Vary', 'Origin');

                return $response;
            }
        )->bind('place');

        return $controllers;
    }
}
