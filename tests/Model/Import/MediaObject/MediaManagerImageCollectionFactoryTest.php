<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\MediaObject;

use CultuurNet\UDB3\Media\Image as MediaImage;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Media\MediaObject as Udb3MediaObjectAggregate;
use CultuurNet\UDB3\Media\MediaObjectNotFoundException;
use CultuurNet\UDB3\Media\Properties\Description as MediaDescription;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Image;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Images;
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
    public function it_should_return_an_image_collection_from_images(): void
    {
        $existingMedia = [
            // JPG image.
            'b170224d-a5c6-40e3-a622-c4bac3a68f3a' => Udb3MediaObjectAggregate::create(
                new Uuid('b170224d-a5c6-40e3-a622-c4bac3a68f3a'),
                MIMEType::fromSubtype('jpeg'),
                new MediaDescription('Example description'),
                new CopyrightHolder('Bob'),
                new Url('https://io.uitdatabank.be/images/b170224d-a5c6-40e3-a622-c4bac3a68f3a.jpg'),
                new Language('en')
            ),
            // MOV file.
            '9bad84d7-8200-4a23-af86-ec4decb3fe86' => Udb3MediaObjectAggregate::create(
                new Uuid('9bad84d7-8200-4a23-af86-ec4decb3fe86'),
                MIMEType::fromSubtype('octet-stream'),
                new MediaDescription('Filmpje'),
                new CopyrightHolder('Bob'),
                new Url('https://io.uitdatabank.be/images/9bad84d7-8200-4a23-af86-ec4decb3fe86.mov'),
                new Language('en')
            ),
            // MOV file.
            'a6a883ac-47c4-4a87-811d-cdb0bfc7e0eb' => Udb3MediaObjectAggregate::create(
                new Uuid('a6a883ac-47c4-4a87-811d-cdb0bfc7e0eb'),
                MIMEType::fromSubtype('octet-stream'),
                new MediaDescription('Filmpje 2'),
                new CopyrightHolder('Bob'),
                new Url('https://io.uitdatabank.be/images/a6a883ac-47c4-4a87-811d-cdb0bfc7e0eb.mov'),
                new Language('nl')
            ),
            // PNG image.
            '502c9436-02cd-4224-a690-04898b7c3a8d' => Udb3MediaObjectAggregate::create(
                new Uuid('502c9436-02cd-4224-a690-04898b7c3a8d'),
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
                function (Uuid $id) use ($existingMedia) {
                    if (isset($existingMedia[$id->toString()])) {
                        return $existingMedia[$id->toString()];
                    } else {
                        throw new MediaObjectNotFoundException();
                    }
                }
            );

        $input = new Images(
            // JPG image with updated description, copyright holder and language.
            new Image(
                new Uuid('b170224d-a5c6-40e3-a622-c4bac3a68f3a'),
                new Language('nl'),
                new Description('Voorbeeld beschrijving (aangepast)'),
                new CopyrightHolder('Bobby')
            ),
            // Does not exist.
            new Image(
                new Uuid('27a317c3-b74d-4352-97f1-9606f7dc0e05'),
                new Language('nl'),
                new Description('Voorbeeld beschrijving'),
                new CopyrightHolder('Bob')
            ),
            // Movie.
            new Image(
                new Uuid('9bad84d7-8200-4a23-af86-ec4decb3fe86'),
                new Language('nl'),
                new Description('Filmpje'),
                new CopyrightHolder('Bob')
            ),
            // Has no type so will be treated as an image but is actually a movie internally.
            new Image(
                new Uuid('a6a883ac-47c4-4a87-811d-cdb0bfc7e0eb'),
                new Language('nl'),
                new Description('Voorbeeld beschrijving 2'),
                new CopyrightHolder('Bob')
            ),
            // PNG image with original description, copyright holder and language.
            new Image(
                new Uuid('502c9436-02cd-4224-a690-04898b7c3a8d'),
                new Language('nl'),
                new Description('PNG Afbeelding'),
                new CopyrightHolder('Bob')
            )
        );

        $expected = ImageCollection::fromArray(
            [
                new MediaImage(
                    new Uuid('b170224d-a5c6-40e3-a622-c4bac3a68f3a'),
                    MIMEType::fromSubtype('jpeg'),
                    new MediaDescription('Voorbeeld beschrijving (aangepast)'),
                    new CopyrightHolder('Bobby'),
                    new Url('https://io.uitdatabank.be/images/b170224d-a5c6-40e3-a622-c4bac3a68f3a.jpg'),
                    new Language('nl')
                ),
                new MediaImage(
                    new Uuid('502c9436-02cd-4224-a690-04898b7c3a8d'),
                    MIMEType::fromSubtype('png'),
                    new MediaDescription('PNG Afbeelding'),
                    new CopyrightHolder('Bob'),
                    new Url('https://io.uitdatabank.be/images/502c9436-02cd-4224-a690-04898b7c3a8d.png'),
                    new Language('nl')
                ),
            ]
        );

        $actual = $this->factory->fromImages($input);

        $this->assertEquals($expected, $actual);
    }
}
