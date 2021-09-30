<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use League\Flysystem\Config;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Visibility;

class ImageStorage
{
    private FilesystemOperator $localFilesystem;

    private FilesystemOperator $s3FileSystem;

    private string $mediaDirectory;

    public function __construct(
        FilesystemOperator $localFilesystem,
        FilesystemOperator $s3FileSystem,
        string $mediaDirectory
    ) {
        $this->localFilesystem = $localFilesystem;
        $this->s3FileSystem = $s3FileSystem;
        $this->mediaDirectory = $mediaDirectory;
    }

    public function store(string $source, string $destination): void
    {
        // Move to the local file system media directory
        $this->localFilesystem->copy(
            $source,
            $this->mediaDirectory . '/' . $destination
        );

        // Upload to the S3 bucket
        $this->s3FileSystem->writeStream(
            $destination,
            $this->localFilesystem->readStream($source),
            [Config::OPTION_VISIBILITY => Visibility::PUBLIC]
        );

        $this->localFilesystem->delete($source);
    }
}
