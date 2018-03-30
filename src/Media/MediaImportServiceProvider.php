<?php

namespace CultuurNet\UDB3\Silex\Media;

use Broadway\EventStore\DBALEventStore;
use Broadway\Serializer\SimpleInterfaceSerializer;
use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\Import\MediaObject\MediaManagerImageCollectionFactory;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Media\ImageUploaderService;
use CultuurNet\UDB3\Media\MediaManager;
use CultuurNet\UDB3\Media\MediaObjectRepository;
use CultuurNet\UDB3\Media\Serialization\MediaObjectSerializer;
use CultuurNet\UDB3\Media\SimplePathGenerator;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
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
