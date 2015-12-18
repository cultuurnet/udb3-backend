<?php

namespace CultuurNet\UDB3\Silex\Media;

use CultuurNet\UDB3\Symfony\MediaController;
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
                return new MediaController(
                    $app['image_uploader'],
                    $app['media_manager']
                );
            }
        );

        /* @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->post('/images', 'media_controller:upload');
        $controllers->get('/images/{id}', 'media_controller:get');

        return $controllers;
    }

}
