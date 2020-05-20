<?php

namespace CultuurNet\UDB3\Event\Events\Image;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Media\Properties\CopyrightHolder;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\Web\Url;

class ImagesImportedFromUDB2Test extends TestCase
{
    /**
     * @test
     */
    public function it_should_be_serializable_and_deserializable_when_collection_is_not_empty()
    {
        $images = ImageCollection::fromArray([
            new Image(
                UUID::fromNative('91bb3d6b-090c-425a-ae4d-6ae0af41a32e'),
                MIMEType::fromSubtype('jpeg'),
                new Description('my best selfie'),
                new CopyrightHolder('Dirkinator'),
                Url::fromNative('http://du.de/media/dsc_00001.jpg'),
                new Language('en')
            ),
        ]);

        $event = new ImagesImportedFromUDB2('c6048768-8cbf-483d-a616-c3241e313383', $images);

        $serializedEvent = $event->serialize();

        $this->assertEquals($event, ImagesImportedFromUDB2::deserialize($serializedEvent));
    }
    /**
     * @test
     */
    public function it_should_be_serializable_and_deserializable_when_collection_is_empty()
    {
        $images = new ImageCollection();

        $event = new ImagesImportedFromUDB2('c6048768-8cbf-483d-a616-c3241e313383', $images);

        $serializedEvent = $event->serialize();

        $this->assertEquals($event, ImagesImportedFromUDB2::deserialize($serializedEvent));
    }
}
