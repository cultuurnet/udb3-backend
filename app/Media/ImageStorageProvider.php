<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use Aws\S3\S3Client;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

final class ImageStorageProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'local_file_system',
            's3_file_system',
            'image_storage',
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'local_file_system',
            function (): Filesystem {
                $localAdapter = new LocalFilesystemAdapter(__DIR__ . '/../../');
                return new Filesystem($localAdapter);
            }
        );

        $container->addShared(
            's3_file_system',
            function () use ($container): Filesystem {
                $s3Client = new S3Client([
                    'credentials' => [
                        'key'    => $container->get('config')['media']['aws']['credentials']['key'],
                        'secret' => $container->get('config')['media']['aws']['credentials']['secret'],
                    ],
                    'region' => $container->get('config')['media']['aws']['region'],
                    'version' => $container->get('config')['media']['aws']['version'],
                ]);
                $s3Adapter = new AwsS3V3Adapter($s3Client, $container->get('config')['media']['aws']['bucket']);
                return new Filesystem($s3Adapter);
            }
        );

        $container->addShared(
            'image_storage',
            function () use ($container): ImageStorage {
                return new ImageStorage(
                    $container->get('local_file_system'),
                    $container->get('s3_file_system'),
                    $container->get('config')['media']['media_directory']
                );
            }
        );
    }
}
