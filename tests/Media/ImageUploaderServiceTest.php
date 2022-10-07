<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use Broadway\CommandHandling\CommandBus;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Exceptions\InvalidFileSize;
use CultuurNet\UDB3\Media\Exceptions\InvalidFileType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\StringLiteral;
use Psr\Http\Message\UploadedFileInterface;
use Zend\Diactoros\Stream;

class ImageUploaderServiceTest extends TestCase
{
    private UUID $fileId;

    private ImageUploaderInterface $uploader;

    /**
     * @var MockObject|UuidGeneratorInterface
     */
    protected $uuidGenerator;

    /**
     * @var MockObject|FilesystemOperator
     */
    protected $filesystem;

    protected string $directory = '/uploads';

    /**
     * @var MockObject|CommandBus
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
        $description = new StringLiteral('file description');
        $copyrightHolder = new CopyrightHolder('Dude Man');
        $language = new Language('en');
        $image = $this->createMock(UploadedFileInterface::class);

        $image
            ->expects($this->once())
            ->method('getError')
            ->willReturn(UPLOAD_ERR_OK);

        $image
            ->expects($this->once())
            ->method('getClientMediaType')
            ->willReturn('video/avi');

        $this->expectException(InvalidFileType::class);
        $this->expectExceptionMessage('The uploaded file has mime type "video/avi" instead of image/png,image/jpeg,image/gif');

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
            ->expects($this->exactly(2))
            ->method('getClientMediaType')
            ->willReturn('image/png');

        $image
            ->expects($this->once())
            ->method('getStream')
            ->willReturn(new Stream(fopen(__DIR__ . '/files/my-image.png', 'rb')));

        $description = new StringLiteral('file description');
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

        $description = new StringLiteral('file description');
        $copyrightHolder = new CopyrightHolder('Dude Man');
        $language = new Language('en');

        $this->expectException(InvalidFileType::class);
        $this->expectExceptionMessage('The file did not upload correctly.');

        $this->uploader->upload($image, $description, $copyrightHolder, $language);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_the_file_type_can_not_be_guessed(): void
    {
        $image = $this->createMock(UploadedFileInterface::class);

        $image
            ->expects($this->once())
            ->method('getError')
            ->willReturn(UPLOAD_ERR_OK);

        $image
            ->expects($this->once())
            ->method('getClientMediaType')
            ->willReturn('');

        $description = new StringLiteral('file description');
        $copyrightHolder = new CopyrightHolder('Dude Man');
        $language = new Language('en');

        $this->expectException(InvalidFileType::class);
        $this->expectExceptionMessage('The type of the uploaded file can not be guessed.');

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
            ->method('getClientMediaType')
            ->willReturn('image/png');

        $image
            ->expects($this->once())
            ->method('getSize')
            ->willReturn(1000001);

        $description = new StringLiteral('file description');
        $copyrightHolder = new CopyrightHolder('Dude Man');
        $language = new Language('en');

        $this->expectException(InvalidFileSize::class);
        $this->expectExceptionMessage('The file size of the uploaded image is too big.');

        $uploader->upload($image, $description, $copyrightHolder, $language);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_the_file_size_is_limited_but_cannot_be_determined(): void
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
            ->method('getClientMediaType')
            ->willReturn('image/png');

        $image
            ->expects($this->once())
            ->method('getSize')
            ->willReturn(0);

        $description = new StringLiteral('file description');
        $copyrightHolder = new CopyrightHolder('Dude Man');
        $language = new Language('en');

        $this->expectException(InvalidFileSize::class);
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
            ->expects($this->exactly(2))
            ->method('getClientMediaType')
            ->willReturn('image/jpeg');

        $image
            ->expects($this->once())
            ->method('getSize')
            ->willReturn(5000);

        $image
            ->expects($this->once())
            ->method('getStream')
            ->willReturn(new Stream(fopen(__DIR__ . '/files/my-image.png', 'rb')));

        $uploader = new ImageUploaderService(
            $this->uuidGenerator,
            $this->commandBus,
            $this->filesystem,
            $this->directory,
            1000000
        );

        $description = new StringLiteral('file description');
        $copyrightHolder = new CopyrightHolder('Dude Man');
        $language = new Language('en');

        $expectedDestination = $this->directory . '/' . $this->fileId->toString() . '.jpeg';

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
