<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use Broadway\CommandHandling\CommandBus;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Media\Exceptions\InvalidFileSize;
use CultuurNet\UDB3\Media\Exceptions\InvalidFileType;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use Laminas\Diactoros\Stream;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

final class ImageUploaderServiceTest extends TestCase
{
    private UUID $fileId;

    private ImageUploaderInterface $uploader;

    /**
     * @var UuidGeneratorInterface&MockObject
     */
    protected $uuidGenerator;

    /**
     * @var FilesystemOperator&MockObject
     */
    protected $filesystem;

    protected string $directory = '/uploads';

    /**
     * @var CommandBus&MockObject
     */
    protected $commandBus;

    public function setUp(): void
    {
        $this->fileId = new UUID('de305d54-75b4-431b-adb2-eb6b9e546014');

        $this->uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $this->filesystem = $this->createMock(FilesystemOperator::class);
        $this->commandBus = $this->createMock(CommandBus::class);

        $this->uploader = new ImageUploaderService(
            $this->uuidGenerator,
            $this->commandBus,
            $this->filesystem,
            $this->directory
        );
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_the_uploaded_file_is_not_an_image(): void
    {
        $description = new Description('file description');
        $copyrightHolder = new CopyrightHolder('Dude Man');
        $language = new Language('en');
        $image = $this->createMock(UploadedFileInterface::class);

        $image
            ->expects($this->once())
            ->method('getError')
            ->willReturn(UPLOAD_ERR_OK);

        $image
            ->expects($this->any())
            ->method('getStream')
            ->willReturn(new Stream(fopen(__DIR__ . '/files/not-an-image.txt', 'rb')));

        $this->expectException(InvalidFileType::class);
        $this->expectExceptionMessage('The uploaded file has mime type "text/plain" instead of image/png,image/jpeg,image/gif');

        $this->uploader->upload($image, $description, $copyrightHolder, $language);
    }

    /**
     * @test
     */
    public function it_should_move_an_uploaded_file_to_the_upload_directory(): void
    {
        $image = $this->createMock(UploadedFileInterface::class);

        $image
            ->expects($this->once())
            ->method('getError')
            ->willReturn(UPLOAD_ERR_OK);

        $image
            ->expects($this->any())
            ->method('getStream')
            ->willReturn(new Stream(fopen(__DIR__ . '/files/my-image.png', 'rb')));

        $image
            ->expects($this->once())
            ->method('getSize')
            ->willReturn(1000001);

        $description = new Description('file description');
        $copyrightHolder = new CopyrightHolder('Dude Man');
        $language = new Language('en');

        $expectedDestination = $this->directory . '/' . $this->fileId->toString() . '.png';

        $generatedUuid = 'de305d54-75b4-431b-adb2-eb6b9e546014';
        $this->uuidGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturn($generatedUuid);

        $this->filesystem
            ->expects($this->once())
            ->method('write')
            ->with($expectedDestination, $this->anything());

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch');

        $imageId = $this->uploader->upload($image, $description, $copyrightHolder, $language);

        $this->assertEquals($generatedUuid, $imageId->toString());
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_the_upload_was_not_successful(): void
    {
        $image = $this->createMock(UploadedFileInterface::class);

        $image
            ->expects($this->once())
            ->method('getError')
            ->willReturn(UPLOAD_ERR_CANT_WRITE);

        $description = new Description('file description');
        $copyrightHolder = new CopyrightHolder('Dude Man');
        $language = new Language('en');

        $this->expectException(InvalidFileType::class);
        $this->expectExceptionMessage('The file did not upload correctly.');

        $this->uploader->upload($image, $description, $copyrightHolder, $language);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_the_file_size_limit_is_exceeded(): void
    {
        $uploader = new ImageUploaderService(
            $this->uuidGenerator,
            $this->commandBus,
            $this->filesystem,
            $this->directory,
            1000000
        );

        $image = $this->createMock(UploadedFileInterface::class);

        $image
            ->expects($this->once())
            ->method('getError')
            ->willReturn(UPLOAD_ERR_OK);

        $image
            ->expects($this->once())
            ->method('getSize')
            ->willReturn(1000001);

        $image
            ->expects($this->any())
            ->method('getStream')
            ->willReturn(new Stream(fopen(__DIR__ . '/files/my-image.png', 'rb')));

        $description = new Description('file description');
        $copyrightHolder = new CopyrightHolder('Dude Man');
        $language = new Language('en');

        $this->expectException(InvalidFileSize::class);
        $this->expectExceptionMessage('The file size of the uploaded image is too big.');

        $uploader->upload($image, $description, $copyrightHolder, $language);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_the_file_size_is_zero(): void
    {
        $uploader = new ImageUploaderService(
            $this->uuidGenerator,
            $this->commandBus,
            $this->filesystem,
            $this->directory,
            1000000
        );

        $image = $this->createMock(UploadedFileInterface::class);

        $image
            ->expects($this->once())
            ->method('getError')
            ->willReturn(UPLOAD_ERR_OK);

        $image
            ->expects($this->once())
            ->method('getSize')
            ->willReturn(0);

        $image
            ->expects($this->any())
            ->method('getStream')
            ->willReturn(new Stream(fopen(__DIR__ . '/files/my-image.png', 'rb')));

        $description = new Description('file description');
        $copyrightHolder = new CopyrightHolder('Dude Man');
        $language = new Language('en');

        $this->expectException(InvalidFileSize::class);
        $this->expectExceptionMessage('The size of the uploaded image must not be 0 bytes.');

        $uploader->upload($image, $description, $copyrightHolder, $language);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_the_file_size_cannot_be_determined(): void
    {
        $uploader = new ImageUploaderService(
            $this->uuidGenerator,
            $this->commandBus,
            $this->filesystem,
            $this->directory,
            1000000
        );

        $image = $this->createMock(UploadedFileInterface::class);

        $image
            ->expects($this->once())
            ->method('getError')
            ->willReturn(UPLOAD_ERR_OK);

        $image
            ->expects($this->once())
            ->method('getSize')
            ->willReturn(null);

        $image
            ->expects($this->any())
            ->method('getStream')
            ->willReturn(new Stream(fopen(__DIR__ . '/files/my-image.png', 'rb')));

        $description = new Description('file description');
        $copyrightHolder = new CopyrightHolder('Dude Man');
        $language = new Language('en');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The size of the uploaded image could not be determined.');

        $uploader->upload($image, $description, $copyrightHolder, $language);
    }

    /**
     * @test
     */
    public function it_should_upload_a_file_that_does_not_exceed_the_maximum_file_size(): void
    {
        $image = $this->createMock(UploadedFileInterface::class);

        $image
            ->expects($this->once())
            ->method('getError')
            ->willReturn(UPLOAD_ERR_OK);

        $image
            ->expects($this->once())
            ->method('getSize')
            ->willReturn(5000);

        $image
            ->expects($this->any())
            ->method('getStream')
            ->willReturn(new Stream(fopen(__DIR__ . '/files/my-image.png', 'rb')));

        $uploader = new ImageUploaderService(
            $this->uuidGenerator,
            $this->commandBus,
            $this->filesystem,
            $this->directory,
            1000000
        );

        $description = new Description('file description');
        $copyrightHolder = new CopyrightHolder('Dude Man');
        $language = new Language('en');

        $expectedDestination = $this->directory . '/' . $this->fileId->toString() . '.png';

        $generatedUuid = 'de305d54-75b4-431b-adb2-eb6b9e546014';
        $this->uuidGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturn($generatedUuid);

        $this->filesystem
            ->expects($this->once())
            ->method('write')
            ->with($expectedDestination, $this->anything());

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch');

        $imageId = $uploader->upload($image, $description, $copyrightHolder, $language);

        $this->assertEquals($generatedUuid, $imageId->toString());
    }
}
