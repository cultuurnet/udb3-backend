<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex\ImageAsset;

use CultuurNet\UDB3\Symfony\ImageAssetController;
use CultuurNet\UDB3\Symfony\JsonLdResponse;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class ControllerProvider implements ControllerProviderInterface
{
    /**
     * @inheritdoc
     */
    public function connect(Application $app)
    {
        $app['image_asset_controller'] = $app->share(
            function (Application $app) {
                return new ImageAssetController($app['image_uploader']);
            }
        );

        /* @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->post('/images', 'image_asset_controller:upload');

        return $controllers;
    }

}
