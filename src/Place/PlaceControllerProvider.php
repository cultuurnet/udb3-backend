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
                    $app['place_editing_service'],
                    $app['event_relations_repository'],
                    $app['media_manager'],
                    $app['place_iri_generator']
                );
            }
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->post('/', 'place_editing_controller:createPlace');
        $controllers->get('/{cdbid}', 'place_controller:get');
        $controllers->put('/{cdbid}/bookingInfo', 'place_editing_controller:updateBookingInfo');
        $controllers->put('/{cdbid}/contactPoint', 'place_editing_controller:updateContactPoint');
        $controllers->put('/{cdbid}/facilities/', 'place_editing_controller:updateFacilities');
        $controllers->put('/{cdbid}/major-info', 'place_editing_controller:updateMajorInfo');
        $controllers->delete('/{cdbid}/organizer/{organizerId}', 'place_editing_controller:deleteOrganizer');
        $controllers->delete('/{cdbid}/typical-age-range', 'place_editing_controller:deleteTypicalAgeRange');
        $controllers->put('/{cdbid}/typical-age-range', 'place_editing_controller:updateTypicalAgeRange');

        $controllers->post('/{itemId}/images/', 'place_editing_controller:addImage');
        $controllers->put('/{itemId}/images/main', 'place_editing_controller:selectMainImage');
        $controllers->delete('/{itemId}/images/{mediaObjectId}', 'place_editing_controller:removeImage');
        $controllers->put('/{itemId}/images/{mediaObjectId}', 'place_editing_controller:updateImage');

        $controllers->get('/places/', 'place_controller:getByPostalCode');

        return $controllers;
    }
}
