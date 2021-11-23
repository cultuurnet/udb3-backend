<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Media;

use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Media\ImageUploaderService;
use CultuurNet\UDB3\Media\MediaUrlMapping;
use CultuurNet\UDB3\Media\MediaManager;
use CultuurNet\UDB3\Media\MediaObjectRepository;
use CultuurNet\UDB3\Media\Serialization\MediaObjectSerializer;
use CultuurNet\UDB3\Media\SimplePathGenerator;
use CultuurNet\UDB3\Silex\AggregateType;
use CultuurNet\UDB3\Silex\Error\LoggerFactory;
use CultuurNet\UDB3\Silex\Error\LoggerName;
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
                return $app['event_store_factory'](
                    AggregateType::MEDIA_OBJECT()
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

        $app['content_url_generator'] = $app->share(
            function (Application $app) {
                return new CallableIriGenerator(
                    function ($filePath) use ($app) {
                        return $app['config']['media']['content_url'] . '/' . $filePath;
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

        $app['media_manager'] = $app->share(
            function (Application $app) {
                $mediaManager = new MediaManager(
                    $app['content_url_generator'],
                    new SimplePathGenerator(),
                    $app['media_object_repository'],
                    $app['image_storage']
                );

                $mediaManager->setLogger(LoggerFactory::create($app, LoggerName::forService('media', 'manager')));

                return $mediaManager;
            }
        );

        $app['media_url_repository'] = $app->share(
            function (Application $app) {
                return new MediaUrlMapping($app['config']['media']['media_url_mapping']);
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
