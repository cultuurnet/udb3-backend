<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex;

use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Response;

class EventsControllerProvider implements ControllerProviderInterface
{
    /**
     * @inheritdoc
     */
    public function connect(Application $app)
    {
        // Creates a new controller based on the default route
        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->post(
            '/event',
            function (Application $app) {
                return new Response(Response::HTTP_OK);
            }
        );

        return $controllers;
    }
}
