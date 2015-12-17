<?php

namespace CultuurNet\UDB3\Silex\Media;

use Broadway\EventStore\DBALEventStore;
use Broadway\Serializer\SimpleInterfaceSerializer;
use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\Media\ImageUploaderService;
use CultuurNet\UDB3\Media\ImageUploadHandler;
use CultuurNet\UDB3\Media\MediaManager;
use CultuurNet\UDB3\Media\MediaObjectRepository;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Silex\Application;
use Silex\ServiceProviderInterface;

class MediaServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $adapter = new Local(__DIR__.'/../..');
        $app['local_file_system'] = new Filesystem($adapter);
        $app['upload_directory'] = '/web/uploads';
        $app['media_directory'] = '/web/media';

        $app['image_uploader'] = $app->share(function (Application $app) {
            return new ImageUploaderService(
                new Version4Generator(),
                $app['event_command_bus'],
                $app['local_file_system'],
                $app['upload_directory']
            );
        });

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
                        $app['event_stream_metadata_enricher']
                    ]
                );

                return $repository;
            }
        );

        $app['media_manager'] = $app->share(function (Application $app) {
            return new MediaManager(
                new CallableIriGenerator(
                    function ($fileName) use ($app) {
                        return $app['config']['url'] . '/media/' . $fileName;
                    }
                ),
                $app['media_object_repository'],
                $app['local_file_system'],
                $app['upload_directory'],
                $app['media_directory']
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