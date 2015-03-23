<?php

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\EventExport\Command\ExportEventsAsCSV;
use CultuurNet\UDB3\EventExport\Command\ExportEventsAsJsonLD;
use CultuurNet\UDB3\EventExport\Command\ExportEventsAsOOXML;
use CultuurNet\UDB3\EventExport\Command\ExportEventsAsPDF;
use CultuurNet\UDB3\EventExport\EventExportQuery;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use ValueObjects\Web\EmailAddress;

class ExportEventsControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        // Creates a new controller based on the default route
        $controllers = $app['controllers_factory'];

        $controllers->post(
            '/json',
            function (Request $request, Application $app) {

                if ($request->request->has('email')) {
                    $email = new EmailAddress($request->request->get('email'));
                } else {
                    $email = null;
                }
                $selection = $request->request->get('selection');
                $include = $request->request->get('include');


                $command = new ExportEventsAsJsonLD(
                    new EventExportQuery(
                        $request->request->get('query')
                    ),
                    $email,
                    $selection,
                    $include
                );

                /** @var \Broadway\CommandHandling\CommandBusInterface $commandBus */
                $commandBus = $app['event_command_bus'];
                $commandId = $commandBus->dispatch($command);

                return JsonResponse::create(
                    ['commandId' => $commandId]
                );
            }
        );

        $controllers->post(
            '/csv',
            function (Request $request, Application $app) {

                if($request->request->has('email')) {
                    $email = new EmailAddress($request->request->get('email'));
                } else {
                    $email = null;
                }
                $selection = $request->request->get('selection');
                $include = $request->request->get('include');

                $command = new ExportEventsAsCSV(
                    new EventExportQuery(
                        $request->request->get('query')
                    ),
                    $email,
                    $selection,
                    $include
                );

                /** @var \Broadway\CommandHandling\CommandBusInterface $commandBus */
                $commandBus = $app['event_command_bus'];
                $commandId = $commandBus->dispatch($command);

                return JsonResponse::create(
                    ['commandId' => $commandId]
                );
            }
        );

        $controllers->post(
            '/ooxml',
            function (Request $request, Application $app) {

                if($request->request->has('email')) {
                    $email = new EmailAddress($request->request->get('email'));
                } else {
                    $email = null;
                }
                $selection = $request->request->get('selection');
                $include = $request->request->get('include');

                $command = new ExportEventsAsOOXML(
                    new EventExportQuery(
                        $request->request->get('query')
                    ),
                    $email,
                    $selection,
                    $include
                );

                /** @var \Broadway\CommandHandling\CommandBusInterface $commandBus */
                $commandBus = $app['event_command_bus'];
                $commandId = $commandBus->dispatch($command);

                return JsonResponse::create(
                    ['commandId' => $commandId]
                );
            }
        );

        $controllers->post(
            '/pdf',
          function (Request $request, Application $app) {

              if($request->request->has('email')) {
                  $email = new EmailAddress($request->request->get('email'));
              } else {
                  $email = null;
              }
              $selection = $request->request->get('selection');
              $customizations = $request->request->get('customizations');

              $command = new ExportEventsAsPDF(
                new EventExportQuery(
                  $request->request->get('query')
                ),
                $email,
                $selection,
                null,
                $customizations
              );

              /** @var \Broadway\CommandHandling\CommandBusInterface $commandBus */
              $commandBus = $app['event_command_bus'];
              $commandId = $commandBus->dispatch($command);

              return JsonResponse::create(
                ['commandId' => $commandId]
              );
          }
        );

        return $controllers;
    }
}
