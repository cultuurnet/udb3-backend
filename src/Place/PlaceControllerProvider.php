<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex\Place;

use CultuurNet\Hydra\PagedCollection;
use CultuurNet\UDB3\EntityServiceInterface;
use CultuurNet\UDB3\Place\ReadModel\Lookup\PlaceLookupServiceInterface;
use CultuurNet\UDB3\Symfony\JsonLdResponse;
use CultuurNet\UDB3\Symfony\Place\PlaceEditingRestController;
use CultuurNet\UDB3\Symfony\Place\PlaceRestController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class PlaceControllerProvider implements ControllerProviderInterface
{
    /**
     * @inheritdoc
     */
    public function connect(Application $app)
    {
        $app['place_controller'] = $app->share(
            function (Application $app) {
                return new PlaceRestController(
                    $app['place_service'],
                    $app['place_lookup']
                );
            }
        );

        $app['place_editing_controller'] = $app->share(
            function (Application $app) {
                return new PlaceEditingRestController(
                    $app['place_service'],
                    $app['place_editing_service'],
                    $app['event_relations_repository'],
                    $app['current_user']
                );
            }
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers
            ->get('place/{cdbid}', 'place_controller:get')
            ->bind('place');

        $controllers->get('/places', 'place_controller:getByPostalCode');

        // @todo Reduce path to /place.
        $controllers->post('api/1.0/place', 'place_editing_controller:createPlace');
        $controllers->post('api/1.0/place/{cdbid}/image', 'place_editing_controller:addImage');

        $controllers->post('place/{cdbid}/nl/description', 'place_editing_controller:updateDescription');
        $controllers->post('place/{cdbid}/typicalAgeRange', 'place_editing_controller:updateTypicalAgeRange');
        $controllers->delete('api/1.0/place/{cdbid}/typicalAgeRange', 'place_editing_controller:deleteTypicalAgeRange');
        $controllers->post('place/{cdbid}/major-info', 'place_editing_controller:updateMajorInfo');
        $controllers->post('place/{cdbid}/bookingInfo', 'place_editing_controller:updateBookingInfo');
        $controllers->post('place/{cdbid}/contactPoint', 'place_editing_controller:updateContactPoint');
        $controllers->post('place/{cdbid}/facilities', 'place_editing_controller:updateFacilities');
        $controllers->post('place/{cdbid}/organizer', 'place_editing_controller:updateOrganizer');
        $controllers->delete('place/{cdbid}/organizer/{organizerId}', 'place_editing_controller:deleteOrganizer');

        return $controllers;
    }
}
