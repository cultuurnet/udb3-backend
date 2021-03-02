<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\Media;

use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Media\Properties\CopyrightHolder;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\Web\Url;

class MediaImporterTest extends TestCase
{
    /**
     * @var MediaImporter
     */
    private $importer;

    /**
     * @var MediaManagerInterface|MockObject
     */
    private $mediaManager;

    /**
     * @var ImageCollectionFactoryInterface|MockObject
     */
    private $imageCollectionFactory;

    public function setup()
    {
        $this->mediaManager = $this->createMock(MediaManagerInterface::class);
        $this->imageCollectionFactory = $this->createMock(ImageCollectionFactoryInterface::class);
        $this->importer = new MediaImporter($this->mediaManager, $this->imageCollectionFactory);
    }

    /**
     * @test
     */
    public function it_should_create_media_objects_from_event_media_when_importing_from_udb2()
    {
        $cdbXml = file_get_contents(__DIR__ . '/../Label/Samples/event.xml');
        $cdbXmlNamespaceUri = \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.3');

        $cdbEvent = EventItemFactory::createEventFromCdbXml(
            $cdbXmlNamespaceUri,
            $cdbXml
        );

        $this->imageCollectionFactory
            ->method('fromUdb2Item')
            ->willReturn(ImageCollection::fromArray([
                new Image(
                    UUID::fromNative('f26433f0-97ef-5c07-8ea9-ef00a64dcb59'),
                    MIMEType::fromNative('image/jpeg'),
                    new Description('no description'),
                    new CopyrightHolder('Zelf gemaakt'),
                    Url::fromNative('http://85.255.197.172/images/20140108/9554d6f6-bed1-4303-8d42-3fcec4601e0e.jpg'),
                    new Language('nl')
                ),
            ]));

        $this->mediaManager
            ->expects($this->once())
            ->method('create')
            ->with(
                UUID::fromNative('f26433f0-97ef-5c07-8ea9-ef00a64dcb59'),
                MIMEType::fromNative('image/jpeg'),
                new Description('no description'),
                new CopyrightHolder('Zelf gemaakt'),
                Url::fromNative('http://85.255.197.172/images/20140108/9554d6f6-bed1-4303-8d42-3fcec4601e0e.jpg'),
                new Language('nl')
            );

        $this->importer->importImages($cdbEvent);
    }
}
