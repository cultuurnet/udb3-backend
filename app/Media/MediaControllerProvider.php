<?php

namespace CultuurNet\UDB3\Silex\Media;

use CultuurNet\UDB3\Symfony\Media\ReadMediaRestController;
use CultuurNet\UDB3\Symfony\Media\EditMediaRestController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class MediaControllerProvider implements ControllerProviderInterface
{
    /**
     * @inheritdoc
     */
    public function connect(Application $app)
    {
        $app['media_controller'] = $app->share(
            function (Application $app) {
                return new ReadMediaRestController(
                    $app['media_manager'],
                    $app['media_object_serializer']
                );
            }
        );

        $app['media_editing_controller'] = $app->share(
            function (Application $app) {
                return new EditMediaRestController(
                    $app['image_uploader']
                );
            }
        );

        /* @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->post('images/', 'media_editing_controller:upload');
        $controllers->get('images/{id}', 'media_controller:get');

        /* @deprecated */
        $controllers->get('media/{id}', 'media_controller:get');

        return $controllers;
    }
}
