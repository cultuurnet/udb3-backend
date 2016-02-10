<?php

namespace CultuurNet\UDB3\Silex\Event;

use CultuurNet\UDB3\Symfony\Event\EditEventRestController;
use CultuurNet\UDB3\Symfony\Event\ReadEventRestController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class EventControllerProvider implements ControllerProviderInterface
{
    /**
     * @inheritdoc
     */
    public function connect(Application $app)
    {
        $app['event_controller'] = $app->share(
            function (Application $app) {
                return new ReadEventRestController(
                    $app['event_service'],
                    $app['event_history_repository']
                );
            }
        );

        $app['event_editing_controller'] = $app->share(
            function (Application $app) {
                return new EditEventRestController(
                    $app['event_service'],
                    $app['event_editor'],
                    $app['used_labels_memory'],
                    $app['current_user'],
                    $app['media_manager'],
                    $app['iri_generator'],
                    $app['event.security']
                );
            }
        );

        /* @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers
            ->get('event/{cdbid}', 'event_controller:get')
            ->bind('event');

        $controllers
            ->get('event/{cdbid}/history', 'event_controller:history')
            ->bind('event-history');

        $controllers->post('api/1.0/event', "event_editing_controller:createEvent");

        $controllers->get('event/{cdbid}/permission', 'event_editing_controller:hasPermission');

        $controllers->post('event/{eventId}/images', 'event_editing_controller:addImage');
        $controllers->post('event/{eventId}/images/{mediaObjectId}', 'event_editing_controller:updateImage');
        $controllers->delete('event/{eventId}/images/{mediaObjectId}', 'event_editing_controller:removeImage');

        $controllers->post('event/{cdbid}/nl/description', 'event_editing_controller:updateDescription');
        $controllers->post('event/{cdbid}/typicalAgeRange', 'event_editing_controller:updateTypicalAgeRange');
        $controllers->delete('api/1.0/event/{cdbid}/typicalAgeRange', 'event_editing_controller:deleteTypicalAgeRange');
        $controllers->post('event/{cdbid}/major-info', 'event_editing_controller:updateMajorInfo');
        $controllers->post('event/{cdbid}/bookingInfo', 'event_editing_controller:updateBookingInfo');
        $controllers->post('event/{cdbid}/contactPoint', 'event_editing_controller:updateContactPoint');
        $controllers->post('event/{cdbid}/facilities', 'event_editing_controller:updateFacilities');
        $controllers->post('event/{cdbid}/organizer', 'event_editing_controller:updateOrganizer');
        $controllers->delete('event/{cdbid}/organizer/{organizerId}', 'event_editing_controller:deleteOrganizer');

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
