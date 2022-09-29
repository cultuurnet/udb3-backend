<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Media;

use CultuurNet\UDB3\Model\Import\MediaObject\MediaManagerImageCollectionFactory;
use Silex\Application;
use Silex\ServiceProviderInterface;

class MediaImportServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app['import_image_collection_factory'] = $app->share(
            function (Application $app) {
                return new MediaManagerImageCollectionFactory(
                    $app['media_manager']
                );
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function boot(Application $app)
    {
    }
}
