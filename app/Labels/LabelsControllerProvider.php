<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Labels;

use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\Http\Label\CreateLabelRequestHandler;
use CultuurNet\UDB3\Http\Label\GetLabelRequestHandler;
use CultuurNet\UDB3\Http\Label\PatchLabelRequestHandler;
use CultuurNet\UDB3\Http\Label\SearchLabelsRequestHandler;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class LabelsControllerProvider implements ControllerProviderInterface
{
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

        $app[SearchLabelsRequestHandler::class] = $app->share(
            fn (Application $app) => new SearchLabelsRequestHandler(
                $app[LabelServiceProvider::JSON_READ_REPOSITORY],
                $app[LabelServiceProvider::QUERY_FACTORY]
            )
        );

        $controllers = $app['controllers_factory'];
        $controllers->post('/', CreateLabelRequestHandler::class);
        $controllers->patch('/{labelId}/', PatchLabelRequestHandler::class);

        $controllers->get('/{labelId}/', GetLabelRequestHandler::class);
        $controllers->get('/', SearchLabelsRequestHandler::class);

        return $controllers;
    }
}
