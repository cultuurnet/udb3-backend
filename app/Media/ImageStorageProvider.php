<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Media;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ImageStorageProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['local_file_system'] = $app->share(
            function ($app) {
                $localAdapter = new LocalFilesystemAdapter(__DIR__ . '/../../');
                return new Filesystem($localAdapter);
            }
        );

        $app['s3_file_system'] = $app->share(
            function ($app) {
                $s3Client = new S3Client([
                    'credentials' => [
                        'key'    => $app['config']['media']['aws']['credentials']['key'],
                        'secret' => $app['config']['media']['aws']['credentials']['secret'],
                    ],
                    'region' => $app['config']['media']['aws']['region'],
                    'version' => $app['config']['media']['aws']['version'],
                ]);
                $s3Adapter = new AwsS3V3Adapter($s3Client, $app['config']['media']['aws']['bucket']);
                return new Filesystem($s3Adapter);
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
