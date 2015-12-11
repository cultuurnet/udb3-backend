<?php

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\Symfony\EventRestController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class EventsControllerProvider implements ControllerProviderInterface
{
    /**
     * @inheritdoc
     */
    public function connect(Application $app)
    {
        $app['event_controller'] = $app->share(
            function (Application $app) {
                return new EventRestController(
                    $app['event_service'],
                    $app['event_editor'],
                    $app['used_labels_memory'],
                    $app['current_user'],
                    null,
                    $app['iri_generator'],
                    $app['event.security']
                );
            }
        );

        /* @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->post('api/1.0/event', "event_controller:createEvent");

        $controllers->get('event/{cdbid}/permission', 'event_controller:hasPermission');

        $controllers->post('api/1.0/event/{cdbid}/image', 'event_controller:addImage');

        $controllers->post('event/{cdbid}/nl/description', 'event_controller:updateDescription');
        $controllers->post('event/{cdbid}/typicalAgeRange', 'event_controller:updateTypicalAgeRange');
        $controllers->delete('api/1.0/event/{cdbid}/typicalAgeRange', 'event_controller:deleteTypicalAgeRange');
        $controllers->post('event/{cdbid}/major-info', 'event_controller:updateMajorInfo');
        $controllers->post('event/{cdbid}/bookingInfo', 'event_controller:updateBookingInfo');
        $controllers->post('event/{cdbid}/contactPoint', 'event_controller:updateContactPoint');
        $controllers->post('event/{cdbid}/facilities', 'event_controller:updateFacilities');
        $controllers->post('event/{cdbid}/organizer', 'event_controller:updateOrganizer');
        $controllers->delete('event/{cdbid}/organizer/{organizerId}', 'event_controller:deleteOrganizer');

        $controllers->post(
            'event/{cdbid}/{lang}/description',
            function (Request $request, Application $app, $cdbid, $lang) {
                /** @var \CultuurNet\UDB3\Event\EventEditingServiceInterface $service */
                $service = $app['event_editor'];

                $response = new JsonResponse();

                $description = $request->request->get('description');
                if (!$description) {
                    return new JsonResponse(['error' => "description required"], 400);
                }

                try {
                    $commandId = $service->translateDescription(
                        $cdbid,
                        new \CultuurNet\UDB3\Language($lang),
                        $request->get('description')
                    );

                    $response->setData(['commandId' => $commandId]);
                } catch (\Exception $e) {
                    $response->setStatusCode(400);
                    $response->setData(['error' => $e->getMessage()]);
                }

                return $response;
            }
        );

        return $controllers;
    }
}
