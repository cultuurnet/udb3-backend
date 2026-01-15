<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use Broadway\EventHandling\EventBus;
use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\AggregateType;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Doctrine\ReadModel\CacheDocumentRepository;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Http\Media\GetMediaRequestHandler;
use CultuurNet\UDB3\Http\Media\UploadMediaRequestHandler;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Media\ReadModel\ImageLDProjector;
use CultuurNet\UDB3\Media\Serialization\MediaObjectSerializer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject\ImageNormalizer;
use GuzzleHttp\Client;

final class MediaServiceProvider extends AbstractServiceProvider
{
    public const IMAGE_JSONLD_REPOSITORY = 'image_jsonld_repository';

    protected function getProvidedServiceNames(): array
    {
        return [
            'image_uploader',
            ImageDownloader::class,
            'media_object_store',
            'media_object_repository',
            'media_object_iri_generator',
            'content_url_generator',
            'media_object_serializer',
            'media_manager',
            'media_url_mapping',
            MediaServiceProvider::IMAGE_JSONLD_REPOSITORY,
            ImageLDProjector::class,
            GetMediaRequestHandler::class,
            UploadMediaRequestHandler::class,
            ImageNormalizer::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'image_uploader',
            function () use ($container) {
                return new ImageUploaderService(
                    new Version4Generator(),
                    $container->get('event_command_bus'),
                    $container->get('local_file_system'),
                    $container->get('config')['media']['upload_directory'],
                    $container->get('config')['media']['file_size_limit'] ?? 1000000
                );
            }
        );

        $container->addShared(
            ImageDownloader::class,
            fn () => new ImageDownloaderService(
                new Client([
                    'timeout' => 30,
                    'connect_timeout' => 10,
                    'allow_redirects' => [
                        'max' => 3,
                        'strict' => true,
                    ],
                ]),
                $container->get('config')['media']['file_size_limit'] ?? 1000000
            )
        );

        $container->addShared(
            'media_object_store',
            function () use ($container) {
                return $container->get('event_store_factory')(
                    AggregateType::media_object()
                );
            }
        );

        $container->addShared(
            'media_object_repository',
            function () use ($container) {
                return new MediaObjectRepository(
                    $container->get('media_object_store'),
                    $container->get(EventBus::class),
                    [
                        $container->get('event_stream_metadata_enricher'),
                    ]
                );
            }
        );

        $container->addShared(
            'media_object_iri_generator',
            function () use ($container) {
                return new CallableIriGenerator(
                    function ($filePath) use ($container) {
                        return $container->get('config')['url'] . '/images/' . $filePath;
                    }
                );
            }
        );

        $container->addShared(
            'content_url_generator',
            function () use ($container) {
                return new CallableIriGenerator(
                    function ($filePath) use ($container) {
                        return $container->get('config')['media']['content_url'] . '/' . $filePath;
                    }
                );
            }
        );

        $container->addShared(
            'media_object_serializer',
            function () use ($container) {
                return new MediaObjectSerializer(
                    $container->get('media_object_iri_generator')
                );
            }
        );

        $container->addShared(
            'media_manager',
            function () use ($container) {
                $mediaManager = new MediaManager(
                    $container->get('content_url_generator'),
                    new SimplePathGenerator(),
                    $container->get('media_object_repository'),
                    $container->get('image_storage')
                );

                $mediaManager->setLogger(LoggerFactory::create($container, LoggerName::forService('media', 'manager')));

                return $mediaManager;
            }
        );

        $container->addShared(
            'media_url_mapping',
            function () use ($container) {
                return new MediaUrlMapping($container->get('config')['media']['media_url_mapping']);
            }
        );

        $container->addShared(
            MediaServiceProvider::IMAGE_JSONLD_REPOSITORY,
            fn () => new CacheDocumentRepository(
                $container->get('cache')('image_jsonld'),
            )
        );

        $container->addShared(
            ImageLDProjector::class,
            function () use ($container) {
                return new ImageLDProjector(
                    $container->get(MediaServiceProvider::IMAGE_JSONLD_REPOSITORY),
                    $container->get('media_object_iri_generator'),
                    $container->get('content_url_generator')
                );
            }
        );

        $container->addShared(
            GetMediaRequestHandler::class,
            function () use ($container) {
                return new GetMediaRequestHandler(
                    $container->get('media_manager'),
                    $container->get('media_object_serializer'),
                    $container->get('media_url_mapping')
                );
            }
        );

        $container->addShared(
            UploadMediaRequestHandler::class,
            function () use ($container) {
                return new UploadMediaRequestHandler(
                    $container->get('image_uploader'),
                    $container->get(ImageDownloader::class),
                    $container->get('media_object_iri_generator')
                );
            }
        );

        $container->addShared(
            ImageNormalizer::class,
            function () use ($container) {
                return new ImageNormalizer(
                    $container->get('media_object_repository'),
                    $container->get('media_object_iri_generator')
                );
            }
        );
    }
}
