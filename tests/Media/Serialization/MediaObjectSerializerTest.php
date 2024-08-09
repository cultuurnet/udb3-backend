<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\Serialization;

use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\MediaObject;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\UnsupportedException;

final class MediaObjectSerializerTest extends TestCase
{
    /**
     * @var MediaObjectSerializer
     */
    protected $serializer;

    /**
     * @var IriGeneratorInterface&MockObject
     */
    protected $iriGenerator;

    public function setUp(): void
    {
        $this->iriGenerator = $this->createMock(IriGeneratorInterface::class);
        $this->serializer = new MediaObjectSerializer($this->iriGenerator);
    }

    /**
     * @test
     */
    public function it_adds_schema_annotations_when_serializing_a_media_object_to_jsonld(): void
    {
        $mediaObject = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/jpg'),
            new Description('my pic'),
            new CopyrightHolder('Dirk Dirkington'),
            new Url('http://foo.bar/media/my_pic.jpg'),
            new Language('en')
        );

        $this->iriGenerator
            ->expects($this->once())
            ->method('iri')
            ->willReturn('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014');

        $expectedJsonld = [
            '@id' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014',
            '@type' => 'schema:ImageObject',
            'id' => 'de305d54-75b4-431b-adb2-eb6b9e546014',
            'thumbnailUrl' => 'http://foo.bar/media/my_pic.jpg',
            'contentUrl' => 'http://foo.bar/media/my_pic.jpg',
            'description' => 'my pic',
            'copyrightHolder' => 'Dirk Dirkington',
            'inLanguage' => 'en',
        ];

        $jsonld = $this->serializer->serialize($mediaObject);

        $this->assertEquals($expectedJsonld, $jsonld);
    }

    /**
     * @test
     */
    public function it_should_serialize_media_objects_with_application_octet_stream_mime_type(): void
    {
        $mediaObject = MediaObject::create(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('application/octet-stream'),
            new Description('my pic'),
            new CopyrightHolder('Dirk Dirkington'),
            new Url('http://foo.bar/media/my_pic.jpg'),
            new Language('en')
        );

        $this->iriGenerator
            ->expects($this->once())
            ->method('iri')
            ->willReturn('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014');

        $expectedJsonld = [
            '@id' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014',
            '@type' => 'schema:mediaObject',
            'id' => 'de305d54-75b4-431b-adb2-eb6b9e546014',
            'thumbnailUrl' => 'http://foo.bar/media/my_pic.jpg',
            'contentUrl' => 'http://foo.bar/media/my_pic.jpg',
            'description' => 'my pic',
            'copyrightHolder' => 'Dirk Dirkington',
            'inLanguage' => 'en',
        ];

        $jsonld = $this->serializer->serialize($mediaObject);

        $this->assertEquals($expectedJsonld, $jsonld);
    }

    /**
     * @test
     */
    public function it_should_serialize_image_objects_with_application_octet_stream_mime_type(): void
    {
        $mediaObject = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('application/octet-stream'),
            new Description('my pic'),
            new CopyrightHolder('Dirk Dirkington'),
            new Url('http://foo.bar/media/my_pic.jpg'),
            new Language('en')
        );

        $this->iriGenerator
            ->expects($this->once())
            ->method('iri')
            ->willReturn('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014');

        $expectedJsonld = [
            '@id' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014',
            '@type' => 'schema:ImageObject',
            'id' => 'de305d54-75b4-431b-adb2-eb6b9e546014',
            'thumbnailUrl' => 'http://foo.bar/media/my_pic.jpg',
            'contentUrl' => 'http://foo.bar/media/my_pic.jpg',
            'description' => 'my pic',
            'copyrightHolder' => 'Dirk Dirkington',
            'inLanguage' => 'en',
        ];

        $jsonld = $this->serializer->serialize($mediaObject);

        $this->assertEquals($expectedJsonld, $jsonld);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_trying_to_serialize_unknown_media_types(): void
    {
        $mediaObject = MediaObject::create(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('video/avi'),
            new Description('The Gleaners'),
            new CopyrightHolder('Jean-François Millet'),
            new Url('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new Language('en')
        );

        $this->expectException(UnsupportedException::class);
        $this->expectExceptionMessage('Unsupported MIME-type "video/avi"');

        $this->serializer->serialize($mediaObject);
    }

    /**
     * @test
     */
    public function it_serializes_mime_type_image_to_image_object(): void
    {
        $mimeType = new MIMEType('image/jpeg');

        $this->assertEquals(
            'schema:ImageObject',
            $this->serializer->serializeMimeType($mimeType)
        );
    }
}
