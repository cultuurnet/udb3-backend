<?php

namespace CultuurNet\UDB3\UDB2\Media;

use CultureFeed_Cdb_Data_Media;
use CultureFeed_Cdb_Item_Base;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Media\Properties\CopyrightHolder;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\Web\Url;

class ImageCollectionFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_return_a_collection_of_images_from_udb2_item()
    {
        $photo = new Image(
            UUID::fromNative('84c4ddea-a00d-5241-bb1a-f4c01cef0a76'),
            MIMEType::fromNative('image/jpeg'),
            new Description('Ruime Activiteit'),
            new CopyrightHolder('Zelf gemaakt'),
            Url::fromNative('http://85.255.197.172/images/20140108/9554d6f6-bed1-4303-8d42-3fcec4601e0e.jpg'),
            new Language('nl')
        );
        $imageweb = new Image(
            UUID::fromNative('96d1d210-9804-55a4-a2c5-6245031a1d4a'),
            MIMEType::fromNative('application/octet-stream'),
            new Description('Ruime Activiteit'),
            new CopyrightHolder('KWB'),
            Url::fromNative('http://testfilm.uitdatabank.be/images/20160531/kwbeensgezind.jpg'),
            new Language('nl')
        );
        $expectedImages = (ImageCollection::fromArray([$photo, $imageweb]));
        $cdbXml = file_get_contents(__DIR__ . '/samples/event_with_udb2_images.xml');
        $cdbXmlNamespaceUri = \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.3');

        $event = EventItemFactory::createEventFromCdbXml($cdbXmlNamespaceUri, $cdbXml);

        $factory = new ImageCollectionFactory();

        $images = $factory->fromUdb2Item($event);

        $this->assertEquals($expectedImages, $images);
    }

    /**
     * @test
     */
    public function it_should_set_the_first_main_udb2_image_as_main_collection_image()
    {
        $image = new Image(
            UUID::fromNative('6f064917-3b55-5459-97fe-4ac15b1e3db3'),
            MIMEType::fromNative('image/jpeg'),
            new Description('KARBIDO ENSEMBLE - The Table (7+)'),
            new CopyrightHolder('Karbido Ensemble'),
            Url::fromNative('http://media.uitdatabank.be/20140418/edb05b66-611b-4829-b8f6-bb31c285ec89.jpg'),
            new Language('nl')
        );
        $expectedImages = (new ImageCollection())->withMain($image);
        $cdbXml = file_get_contents(__DIR__ . '/samples/event_with_main_imageweb.xml');
        $cdbXmlNamespaceUri = \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.3');

        $event = EventItemFactory::createEventFromCdbXml($cdbXmlNamespaceUri, $cdbXml);

        $factory = new ImageCollectionFactory();

        $images = $factory->fromUdb2Item($event);

        $this->assertEquals($expectedImages, $images);
    }

    /**
     * @test
     */
    public function it_should_only_pick_the_dutch_images_from_an_udb2_item()
    {
        $image = new Image(
            UUID::fromNative('84c4ddea-a00d-5241-bb1a-f4c01cef0a76'),
            MIMEType::fromNative('image/jpeg'),
            new Description('Ruime Activiteit'),
            new CopyrightHolder('Zelf gemaakt'),
            Url::fromNative('http://85.255.197.172/images/20140108/9554d6f6-bed1-4303-8d42-3fcec4601e0e.jpg'),
            new Language('nl')
        );
        $expectedImages = (new ImageCollection())->with($image);
        $cdbXml = file_get_contents(__DIR__ . '/samples/event_with_dutch_and_french_images.xml');
        $cdbXmlNamespaceUri = \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.3');

        $event = EventItemFactory::createEventFromCdbXml($cdbXmlNamespaceUri, $cdbXml);

        $factory = new ImageCollectionFactory();

        $images = $factory->fromUdb2Item($event);

        $this->assertEquals($expectedImages, $images);
    }

    /**
     * @test
     */
    public function it_should_identify_images_using_a_configurable_regex()
    {
        $regex = 'https?:\/\/udb-silex\.dev\/web\/media\/(?<uuid>[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12})\.jpg';

        $image = new Image(
            UUID::fromNative('edb05b66-611b-4829-b8f6-bb31c285ec89'),
            MIMEType::fromNative('image/jpeg'),
            new Description('my best selfie'),
            new CopyrightHolder('my dog'),
            Url::fromNative('http://udb-silex.dev/web/media/edb05b66-611b-4829-b8f6-bb31c285ec89.jpg'),
            new Language('nl')
        );
        $expectedImages = (new ImageCollection())->withMain($image);
        $cdbXml = file_get_contents(__DIR__ . '/samples/event_with_udb3_image.xml');
        $cdbXmlNamespaceUri = \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.3');

        $event = EventItemFactory::createEventFromCdbXml($cdbXmlNamespaceUri, $cdbXml);

        $factory = (new ImageCollectionFactory())->withUuidRegex($regex);

        $images = $factory->fromUdb2Item($event);
        $this->assertEquals($expectedImages, $images);
    }

    /**
     * @param CultureFeed_Cdb_Item_Base $cdbItem
     * @return CultureFeed_Cdb_Data_Media
     */
    private function getMedia(CultureFeed_Cdb_Item_Base $cdbItem)
    {
        $details = $cdbItem->getDetails();
        $details->rewind();

        return $details
            ->current()
            ->getMedia();
    }
}
