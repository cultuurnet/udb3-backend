<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\Repository;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Media\Commands\UploadImage;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class MediaManagerTest extends TestCase
{
    private MediaManager $mediaManager;

    private Repository&MockObject $repository;

    private IriGeneratorInterface&MockObject $iriGenerator;

    private PathGeneratorInterface&MockObject $pathGenerator;

    private ImageStorage&MockObject $imageStorage;

    public function setUp(): void
    {
        $this->repository = $this->createMock(Repository::class);
        $this->iriGenerator = $this->createMock(IriGeneratorInterface::class);
        $this->pathGenerator = $this->createMock(PathGeneratorInterface::class);
        $this->imageStorage = $this->createMock(ImageStorage::class);

        $this->mediaManager = new MediaManager(
            $this->iriGenerator,
            $this->pathGenerator,
            $this->repository,
            $this->imageStorage
        );
    }

    /**
     * @test
     */
    public function it_should_log_the_file_id_after_a_media_object_is_created_for_an_uploaded_image(): void
    {
        $command = new UploadImage(
            new Uuid('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new Description('description'),
            new CopyrightHolder('copyright'),
            '/uploads/de305d54-75b4-431b-adb2-eb6b9e546014.png',
            new Language('en')
        );

        $logger = $this->createMock(LoggerInterface::class);
        $this->mediaManager->setLogger($logger);

        $this->pathGenerator
            ->expects($this->once())
            ->method('path')
            ->willReturn('de305d54-75b4-431b-adb2-eb6b9e546014.png');

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
    public function it_should_move_a_file_to_the_media_directory_when_uploading(): void
    {
        $command = new UploadImage(
            new Uuid('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new Description('description'),
            new CopyrightHolder('copyright'),
            '/uploads/de305d54-75b4-431b-adb2-eb6b9e546014.png',
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

        $this->repository
            ->expects($this->once())
            ->method('load')
            ->willThrowException(new AggregateNotFoundException());

        $this->imageStorage->expects($this->once())
            ->method('store')
            ->with('/uploads/de305d54-75b4-431b-adb2-eb6b9e546014.png', 'de305d54-75b4-431b-adb2-eb6b9e546014.png');

        $this->mediaManager->handleUploadImage($command);
    }

    /**
     * @test
     */
    public function it_can_retrieve_an_image_by_id(): void
    {
        $id = 'de305d54-75b4-431b-adb2-eb6b9e546014';
        $fileType = new MIMEType('image/png');
        $description = new Description('The Gleaners');
        $copyrightHolder = new CopyrightHolder('Jean-François Millet');
        $location = new Url('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png');
        $language = new Language('en');

        $mediaObject = MediaObject::create(
            new Uuid($id),
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

        $image = $this->mediaManager->getImage(new Uuid($id));

        $expectedImage = new Image(
            new Uuid($id),
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
    public function it_throws_an_exception_when_a_media_object_is_not_found(): void
    {
        $id = 'de305d54-75b4-431b-adb2-eb6b9e546014';

        $this->repository
            ->expects($this->once())
            ->method('load')
            ->with($id)
            ->willThrowException(new AggregateNotFoundException());

        $this->expectException(MediaObjectNotFoundException::class);
        $this->expectExceptionMessage("Media object with id '" . $id . "' not found");

        $this->mediaManager->get(new Uuid($id));
    }
}
