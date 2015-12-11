<?php

namespace CultuurNet\UDB3\Silex\ImageAsset;

use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\ImageAsset\ImageUploaderService;
use CultuurNet\UDB3\ImageAsset\ImageUploadHandler;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app['upload_directory'] = realpath(__DIR__.'/../../web/uploads');

        $app['image_directory'] = realpath(__DIR__.'/../../web/image-assets');

        $app['image_uploader'] = $app->share(function (Application $app) {
            return new ImageUploaderService(
                new Version4Generator(),
                $app['event_command_bus'],
                $app['upload_directory'],
                $app['image_directory']
            );
        });
    }

    /**
     * @inheritdoc
     */
    public function boot(Application $app)
    {

    }
}