<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex\Place;

use CultuurNet\UDB3\Symfony\Place\EditPlaceRestController;
use CultuurNet\UDB3\Symfony\Place\ReadPlaceRestController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class PlaceControllerProvider implements ControllerProviderInterface
{
    /**
     * @inheritdoc
     */
    public function connect(Application $app)
    {
        $app['place_controller'] = $app->share(
            function (Application $app) {
                return new ReadPlaceRestController(
                    $app['place_service'],
                    $app['place_lookup']
                );
            }
        );

        $app['place_editing_controller'] = $app->share(
            function (Application $app) {
                return new EditPlaceRestController(
                    $app['place_service'],
                    $app['place_editing_service'],
                    $app['event_relations_repository'],
                    $app['current_user'],
                    $app['place.security'],
                    $app['media_manager']
                );
            }
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers
            ->get('place/{cdbid}', 'place_controller:get')
            ->bind('place');
        $controllers->delete('place/{cdbid}', 'place_editing_controller:deletePlace');
        $controllers->get('place/{cdbid}/events', 'place_editing_controller:getEvents');

        $controllers->get('places', 'place_controller:getByPostalCode');

        // @todo Reduce path to /place.
        $controllers->post('api/1.0/place', 'place_editing_controller:createPlace');

        $controllers->post('place/{itemId}/images', 'place_editing_controller:addImage');
        $controllers->post('place/{itemId}/images/main', 'place_editing_controller:selectMainImage');
        $controllers->post('place/{itemId}/images/{mediaObjectId}', 'place_editing_controller:updateImage');
        $controllers->delete('place/{itemId}/images/{mediaObjectId}', 'place_editing_controller:removeImage');

        $controllers->post('place/{cdbid}/nl/description', 'place_editing_controller:updateDescription');
        $controllers->post('place/{cdbid}/typicalAgeRange', 'place_editing_controller:updateTypicalAgeRange');
        $controllers->delete('api/1.0/place/{cdbid}/typicalAgeRange', 'place_editing_controller:deleteTypicalAgeRange');
        $controllers->post('place/{cdbid}/major-info', 'place_editing_controller:updateMajorInfo');
        $controllers->post('place/{cdbid}/bookingInfo', 'place_editing_controller:updateBookingInfo');
        $controllers->post('place/{cdbid}/contactPoint', 'place_editing_controller:updateContactPoint');
        $controllers->post('place/{cdbid}/facilities', 'place_editing_controller:updateFacilities');
        $controllers->post('place/{cdbid}/organizer', 'place_editing_controller:updateOrganizer');
        $controllers->delete('place/{cdbid}/organizer/{organizerId}', 'place_editing_controller:deleteOrganizer');
        $controllers->get('place/{cdbid}/permission', 'place_editing_controller:hasPermission');

        return $controllers;
    }
}
