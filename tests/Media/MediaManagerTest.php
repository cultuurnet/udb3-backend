<?php

namespace CultuurNet\UDB3\Media;

use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\RepositoryInterface;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Commands\UploadImage;
use CultuurNet\UDB3\Media\Properties\CopyrightHolder;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use League\Flysystem\FilesystemInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

class MediaManagerTest extends TestCase
{

    /**
     * @var MediaManager
     */
    protected $mediaManager;

    /**
     * @var RepositoryInterface|MockObject
     */
    protected $repository;

    /**
     * @var IriGeneratorInterface|MockObject
     */
    protected $iriGenerator;

    /**
     * @var PathGeneratorInterface|MockObject
     */
    protected $pathGenerator;

    /**
     * @var string
     */
    protected $mediaDirectory = '/media';

    /**
     * @var FilesystemInterface|MockObject;
     */
    protected $filesystem;

    public function setUp()
    {
        $this->repository = $this->createMock(RepositoryInterface::class);
        $this->iriGenerator = $this->createMock(IriGeneratorInterface::class);
        $this->pathGenerator = $this->createMock(PathGeneratorInterface::class);
        $this->filesystem = $this->createMock(FilesystemInterface::class);

        $this->mediaManager = new MediaManager(
            $this->iriGenerator,
            $this->pathGenerator,
            $this->repository,
            $this->filesystem,
            $this->mediaDirectory
        );
    }

    /**
     * @test
     */
    public function it_should_log_the_file_id_after_a_media_object_is_created_for_an_uploaded_image()
    {
        $command = new UploadImage(
            UUID::fromNative('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            StringLiteral::fromNative('description'),
            StringLiteral::fromNative('copyright'),
            StringLiteral::fromNative('/uploads/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new Language('en')
        );

        $logger = $this->createMock(LoggerInterface::class);
        $this->mediaManager->setLogger($logger);

        $this->iriGenerator
            ->expects($this->once())
            ->method('iri')
            ->willReturn('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png');

        $this->repository
            ->expects($this->once())
            ->method('load')
            ->willThrowException(new AggregateNotFoundException());

        $logger
            ->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                [$this->equalTo('No existing media with id: de305d54-75b4-431b-adb2-eb6b9e546014 found. Creating a new Media Object!')],
                [$this->equalTo('job_info')]
            );

        $this->mediaManager->handleUploadImage($command);
    }

    /**
     * @test
     */
    public function it_should_move_a_file_to_the_media_directory_when_uploading()
    {
        $command = new UploadImage(
            UUID::fromNative('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            StringLiteral::fromNative('description'),
            StringLiteral::fromNative('copyright'),
            StringLiteral::fromNative('/uploads/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new Language('en')
        );

        $this->pathGenerator
            ->expects($this->once())
            ->method('path')
            ->willReturn('de305d54-75b4-431b-adb2-eb6b9e546014.png');

        $this->iriGenerator
            ->expects($this->once())
            ->method('iri')
            ->willReturn('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png');

        $this->filesystem
            ->expects($this->once())
            ->method('rename')
            ->with(
                '/uploads/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                '/media/de305d54-75b4-431b-adb2-eb6b9e546014.png'
            );

        $this->mediaManager->handleUploadImage($command);
    }

    /**
     * @test
     */
    public function it_can_retrieve_an_image_by_id()
    {
        $id = 'de305d54-75b4-431b-adb2-eb6b9e546014';
        $fileType = new MIMEType('image/png');
        $description = new Description('sexy ladies without clothes');
        $copyrightHolder = new CopyrightHolder('Bart Ramakers');
        $location = Url::fromNative('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png');
        $language = new Language('en');

        $mediaObject = MediaObject::create(
            new UUID($id),
            $fileType,
            $description,
            $copyrightHolder,
            $location,
            $language
        );

        $this->repository
            ->expects($this->once())
            ->method('load')
            ->with($id)
            ->willReturn($mediaObject);

        $image = $this->mediaManager->getImage(new UUID($id));

        $expectedImage = new Image(
            new UUID($id),
            $fileType,
            $description,
            $copyrightHolder,
            $location,
            $language
        );

        $this->assertEquals($expectedImage, $image);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_a_media_object_is_not_found()
    {
        $id = 'de305d54-75b4-431b-adb2-eb6b9e546014';

        $this->repository
            ->expects($this->once())
            ->method('load')
            ->with($id)
            ->willThrowException(new AggregateNotFoundException());

        $this->expectException(
            MediaObjectNotFoundException::class,
            "Media object with id '" . $id . "' not found"
        );

        $this->mediaManager->get(new UUID($id));
    }
}
