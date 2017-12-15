<?php

namespace CultuurNet\UDB3\Silex\Media;

use Broadway\EventStore\DBALEventStore;
use Broadway\Serializer\SimpleInterfaceSerializer;
use Broadway\UuidGenerator\Rfc4122\Version4Generator;
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

class MediaServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app['image_uploader'] = $app->share(
            function (Application $app) {
                return new ImageUploaderService(
                    new Version4Generator(),
                    $app['event_command_bus'],
                    $app['local_file_system'],
                    $app['media.upload_directory'],
                    $app['media.file_size_limit']
                );
            }
        );

        $app['media_object_store'] = $app->share(
            function ($app) {
                return new DBALEventStore(
                    $app['dbal_connection'],
                    $app['eventstore_payload_serializer'],
                    new SimpleInterfaceSerializer(),
                    'media_objects'
                );
            }
        );

        $app['media_object_repository'] = $app->share(
            function ($app) {
                $repository = new MediaObjectRepository(
                    $app['media_object_store'],
                    $app['event_bus'],
                    [
                        $app['event_stream_metadata_enricher'],
                    ]
                );

                return $repository;
            }
        );

        $app['media_object_iri_generator'] = $app->share(
            function (Application $app) {
                return new CallableIriGenerator(
                    function ($filePath) use ($app) {
                        return $app['config']['url'] . '/images/' . $filePath;
                    }
                );
            }
        );

        $app['media_object_serializer'] = $app->share(
            function (Application $app) {
                return new MediaObjectSerializer(
                    $app['media_object_iri_generator']
                );
            }
        );

        $app['logger.media_manager'] = $app->share(
            function () {
                $logger = new Logger('media-manager');
                $logger->pushHandler(new StreamHandler(__DIR__ . '/../../log/media_manager.log'));

                return $logger;
            }
        );

        $app['media_manager'] = $app->share(
            function (Application $app) {
                $mediaManager = new MediaManager(
                    $app['media_object_iri_generator'],
                    new SimplePathGenerator(),
                    $app['media_object_repository'],
                    $app['local_file_system'],
                    $app['media.media_directory']
                );

                $mediaManager->setLogger($app['logger.media_manager']);

                return $mediaManager;
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
