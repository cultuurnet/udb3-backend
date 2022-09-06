<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Media;

use CultuurNet\UDB3\Http\Media\GetMediaRequestHandler;
use CultuurNet\UDB3\Http\Media\UploadMediaRequestHandler;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class MediaControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app): ControllerCollection
    {
        $app[GetMediaRequestHandler::class] = $app->share(
            function (Application $app) {
                return new GetMediaRequestHandler(
                    $app['media_manager'],
                    $app['media_object_serializer'],
                    $app['media_url_mapping']
                );
            }
        );

        $app[UploadMediaRequestHandler::class] = $app->share(
            function (Application $app) {
                return new UploadMediaRequestHandler(
                    $app['image_uploader'],
                    $app['media_object_iri_generator']
                );
            }
        );

        /* @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->post('/images/', UploadMediaRequestHandler::class);
        $controllers->get('/images/{id}/', GetMediaRequestHandler::class);

        /* @deprecated */
        $controllers->get('/media/{id}/', GetMediaRequestHandler::class);

        return $controllers;
    }
}
