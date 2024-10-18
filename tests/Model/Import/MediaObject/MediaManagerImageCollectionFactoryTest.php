<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\MediaObject;

use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Media\MediaObject as Udb3MediaObjectAggregate;
use CultuurNet\UDB3\Media\MediaObjectNotFoundException;
use CultuurNet\UDB3\Media\Properties\Description as MediaDescription;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\MediaObject;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\MediaObjectReference;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\MediaObjectReferences;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\MediaObjectType;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MediaManagerImageCollectionFactoryTest extends TestCase
{
    /**
     * @var MediaManagerInterface&MockObject
     */
    private $mediaManager;

    private MediaManagerImageCollectionFactory $factory;

    public function setUp(): void
    {
        $this->mediaManager = $this->createMock(MediaManagerInterface::class);
        $this->factory = new MediaManagerImageCollectionFactory($this->mediaManager);
    }

    /**
     * @test
     */
    public function it_should_return_an_image_collection_with_an_image_for_each_image_in_the_list_of_media_objects(): void
    {
        $existingMedia = [
            // JPG image.
            'b170224d-a5c6-40e3-a622-c4bac3a68f3a' => Udb3MediaObjectAggregate::create(
                new UUID('b170224d-a5c6-40e3-a622-c4bac3a68f3a'),
                MIMEType::fromSubtype('jpeg'),
                new MediaDescription('Example description'),
                new CopyrightHolder('Bob'),
                new Url('https://io.uitdatabank.be/images/b170224d-a5c6-40e3-a622-c4bac3a68f3a.jpg'),
                new Language('en')
            ),
            // MOV file.
            '9bad84d7-8200-4a23-af86-ec4decb3fe86' => Udb3MediaObjectAggregate::create(
                new UUID('9bad84d7-8200-4a23-af86-ec4decb3fe86'),
                MIMEType::fromSubtype('octet-stream'),
                new MediaDescription('Filmpje'),
                new CopyrightHolder('Bob'),
                new Url('https://io.uitdatabank.be/images/9bad84d7-8200-4a23-af86-ec4decb3fe86.mov'),
                new Language('en')
            ),
            // MOV file.
            'a6a883ac-47c4-4a87-811d-cdb0bfc7e0eb' => Udb3MediaObjectAggregate::create(
                new UUID('a6a883ac-47c4-4a87-811d-cdb0bfc7e0eb'),
                MIMEType::fromSubtype('octet-stream'),
                new MediaDescription('Filmpje 2'),
                new CopyrightHolder('Bob'),
                new Url('https://io.uitdatabank.be/images/a6a883ac-47c4-4a87-811d-cdb0bfc7e0eb.mov'),
                new Language('nl')
            ),
            // PNG image.
            '502c9436-02cd-4224-a690-04898b7c3a8d' => Udb3MediaObjectAggregate::create(
                new UUID('502c9436-02cd-4224-a690-04898b7c3a8d'),
                MIMEType::fromSubtype('png'),
                new MediaDescription('PNG Afbeelding'),
                new CopyrightHolder('Bob'),
                new Url('https://io.uitdatabank.be/images/502c9436-02cd-4224-a690-04898b7c3a8d.png'),
                new Language('nl')
            ),
        ];

        $this->mediaManager->expects($this->any())
            ->method('get')
            ->willReturnCallback(
                function (UUID $id) use ($existingMedia) {
                    if (isset($existingMedia[$id->toString()])) {
                        return $existingMedia[$id->toString()];
                    } else {
                        throw new MediaObjectNotFoundException();
                    }
                }
            );

        $input = new MediaObjectReferences(
            // JPG image with updated description, copyright holder and language.
            MediaObjectReference::createWithMediaObjectId(
                new UUID('b170224d-a5c6-40e3-a622-c4bac3a68f3a'),
                new Description('Voorbeeld beschrijving (aangepast)'),
                new CopyrightHolder('Bobby'),
                new Language('nl')
            ),
            // Does not exist.
            MediaObjectReference::createWithMediaObjectId(
                new UUID('27a317c3-b74d-4352-97f1-9606f7dc0e05'),
                new Description('Voorbeeld beschrijving'),
                new CopyrightHolder('Bob'),
                new Language('nl')
            ),
            // Movie.
            MediaObjectReference::createWithEmbeddedMediaObject(
                new MediaObject(
                    new UUID('9bad84d7-8200-4a23-af86-ec4decb3fe86'),
                    MediaObjectType::mediaObject(),
                    new Url('https://io.uitdatabank.be/media/9bad84d7-8200-4a23-af86-ec4decb3fe86.mov'),
                    new Url('https://io.uitdatabank.be/media/9bad84d7-8200-4a23-af86-ec4decb3fe86.jpg')
                ),
                new Description('Filmpje'),
                new CopyrightHolder('Bob'),
                new Language('nl')
            ),
            // Has no type so will be treated as an image but is actually a movie internally.
            MediaObjectReference::createWithMediaObjectId(
                new UUID('a6a883ac-47c4-4a87-811d-cdb0bfc7e0eb'),
                new Description('Voorbeeld beschrijving 2'),
                new CopyrightHolder('Bob'),
                new Language('nl')
            ),
            // PNG image with original description, copyright holder and language.
            MediaObjectReference::createWithEmbeddedMediaObject(
                new MediaObject(
                    new UUID('502c9436-02cd-4224-a690-04898b7c3a8d'),
                    MediaObjectType::imageObject(),
                    new Url('https://io.uitdatabank.be/media/502c9436-02cd-4224-a690-04898b7c3a8d.png'),
                    new Url('https://io.uitdatabank.be/media/502c9436-02cd-4224-a690-04898b7c3a8d.png')
                ),
                new Description('PNG Afbeelding'),
                new CopyrightHolder('Bob'),
                new Language('nl')
            )
        );

        $expected = ImageCollection::fromArray(
            [
                new Image(
                    new UUID('b170224d-a5c6-40e3-a622-c4bac3a68f3a'),
                    MIMEType::fromSubtype('jpeg'),
                    new MediaDescription('Voorbeeld beschrijving (aangepast)'),
                    new CopyrightHolder('Bobby'),
                    new Url('https://io.uitdatabank.be/images/b170224d-a5c6-40e3-a622-c4bac3a68f3a.jpg'),
                    new Language('nl')
                ),
                new Image(
                    new UUID('502c9436-02cd-4224-a690-04898b7c3a8d'),
                    MIMEType::fromSubtype('png'),
                    new MediaDescription('PNG Afbeelding'),
                    new CopyrightHolder('Bob'),
                    new Url('https://io.uitdatabank.be/images/502c9436-02cd-4224-a690-04898b7c3a8d.png'),
                    new Language('nl')
                ),
            ]
        );

        $actual = $this->factory->fromMediaObjectReferences($input);

        $this->assertEquals($expected, $actual);
    }
}
