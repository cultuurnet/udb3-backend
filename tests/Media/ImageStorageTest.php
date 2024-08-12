<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToWriteFile;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImageStorageTest extends TestCase
{
    private string $mediaDirectory = '/media';

    /**
     * @var FilesystemOperator&MockObject;
     */
    private $localFilesystem;

    /**
     * @var FilesystemOperator&MockObject;
     */
    private $s3Filesystem;

    private ImageStorage $imageStorage;

    public function setUp(): void
    {
        $this->localFilesystem = $this->createMock(FilesystemOperator::class);
        $this->s3Filesystem = $this->createMock(FilesystemOperator::class);
        $this->imageStorage = new ImageStorage(
            $this->localFilesystem,
            $this->s3Filesystem,
            $this->mediaDirectory
        );
    }

    /**
     * @test
     */
    public function it_can_store_an_image_in_the_cloud(): void
    {
        $this->localFilesystem
            ->expects($this->once())
            ->method('readStream')
            ->with(
                '/uploads/de305d54-75b4-431b-adb2-eb6b9e546014.png',
            );

        $this->s3Filesystem
            ->expects($this->once())
            ->method('writeStream');

        $this->localFilesystem
            ->expects($this->once())
            ->method('delete')
            ->with(
                '/uploads/de305d54-75b4-431b-adb2-eb6b9e546014.png',
            );

        $this->imageStorage->store(
            '/uploads/de305d54-75b4-431b-adb2-eb6b9e546014.png',
            'de305d54-75b4-431b-adb2-eb6b9e546014.png'
        );
    }

    /**
     * @test
     */
    public function it_can_store_an_image_locally_if_upload_fails(): void
    {
        $this->localFilesystem
            ->expects($this->once())
            ->method('readStream')
            ->with(
                '/uploads/de305d54-75b4-431b-adb2-eb6b9e546014.png',
            );

        $this->s3Filesystem
            ->expects($this->once())
            ->method('writeStream')
            ->willThrowException(new UnableToWriteFile());

        $this->expectException(\Throwable::class);
        $this->localFilesystem
            ->expects($this->once())
            ->method('copy')
            ->with(
                '/uploads/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                '/media/de305d54-75b4-431b-adb2-eb6b9e546014.png'
            );

        $this->localFilesystem
            ->expects($this->once())
            ->method('delete')
            ->with(
                '/uploads/de305d54-75b4-431b-adb2-eb6b9e546014.png',
            );

        $this->imageStorage->store(
            '/uploads/de305d54-75b4-431b-adb2-eb6b9e546014.png',
            'de305d54-75b4-431b-adb2-eb6b9e546014.png'
        );
    }
}
