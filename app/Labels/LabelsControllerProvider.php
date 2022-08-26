<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Labels;

use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\Http\Label\CreateLabelRequestHandler;
use CultuurNet\UDB3\Http\Label\GetLabelRequestHandler;
use CultuurNet\UDB3\Http\Label\PatchLabelRequestHandler;
use CultuurNet\UDB3\Http\Label\ReadRestController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class LabelsControllerProvider implements ControllerProviderInterface
{
    public const READ_REST_CONTROLLER = 'labels.read_rest_controller';

    public function connect(Application $app): ControllerCollection
    {
        $app[CreateLabelRequestHandler::class] = $app->share(
            fn (Application $app) => new CreateLabelRequestHandler(
                $app['event_command_bus'],
                new Version4Generator()
            )
        );

        $app[PatchLabelRequestHandler::class] = $app->share(
            fn (Application $app) => new PatchLabelRequestHandler($app['event_command_bus'])
        );

        $app[GetLabelRequestHandler::class] = $app->share(
            fn (Application $app) => new GetLabelRequestHandler($app[LabelServiceProvider::JSON_READ_REPOSITORY])
        );

        $controllers = $app['controllers_factory'];
        $controllers->post('/', CreateLabelRequestHandler::class);
        $controllers->patch('/{labelId}/', PatchLabelRequestHandler::class);

        $controllers->get('/{labelId}/', GetLabelRequestHandler::class);

        $this->setUpReadRestController($app);

        return $this->setControllerPaths($controllers);
    }


    private function setUpReadRestController(Application $app): void
    {
        $app[self::READ_REST_CONTROLLER] = $app->share(
            function (Application $app) {
                return new ReadRestController(
                    $app[LabelServiceProvider::JSON_READ_REPOSITORY],
                    $app[LabelServiceProvider::QUERY_FACTORY]
                );
            }
        );
    }

    private function setControllerPaths(ControllerCollection $controllers): ControllerCollection
    {
        $controllers->get('/', self::READ_REST_CONTROLLER . ':search');

        return $controllers;
    }
}
