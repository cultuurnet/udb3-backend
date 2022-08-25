<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Labels;

use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\Http\Label\CreateLabelRequestHandler;
use CultuurNet\UDB3\Http\Label\EditRestController;
use CultuurNet\UDB3\Http\Label\ReadRestController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class LabelsControllerProvider implements ControllerProviderInterface
{
    public const READ_REST_CONTROLLER = 'labels.read_rest_controller';
    public const EDIT_REST_CONTROLLER = 'labels.edit_rest_controller';

    public function connect(Application $app): ControllerCollection
    {
        $app[CreateLabelRequestHandler::class] = $app->share(
            fn (Application $app) => new CreateLabelRequestHandler(
                $app['event_command_bus'],
                new Version4Generator()
            )
        );

        $controllers = $app['controllers_factory'];
        $controllers->post('/', CreateLabelRequestHandler::class);

        $this->setUpReadRestController($app);
        $this->setUpEditRestController($app);

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


    private function setUpEditRestController(Application $app): void
    {
        $app[self::EDIT_REST_CONTROLLER] = $app->share(
            function (Application $app) {
                return new EditRestController(
                    $app[LabelServiceProvider::WRITE_SERVICE]
                );
            }
        );
    }

    private function setControllerPaths(ControllerCollection $controllers): ControllerCollection
    {
        $controllers->get('/{id}/', self::READ_REST_CONTROLLER . ':get');
        $controllers->patch('/{id}/', self::EDIT_REST_CONTROLLER . ':patch');
        $controllers->get('/', self::READ_REST_CONTROLLER . ':search');

        return $controllers;
    }
}
