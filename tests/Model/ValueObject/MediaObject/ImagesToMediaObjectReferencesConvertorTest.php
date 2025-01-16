<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Media\MediaObject;
use CultuurNet\UDB3\Media\MediaObjectRepository;
use CultuurNet\UDB3\Media\Properties\Description as DescriptionProperties;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\MediaObject as MediaObjectDto;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImagesToMediaObjectReferencesConvertorTest extends TestCase
{
    /**
     * @var MediaObjectRepository|MockObject
     */
    private $mediaObjectRepository;

    private ImagesToMediaObjectReferencesConvertor $convertor;

    protected function setUp(): void
    {
        $this->mediaObjectRepository = $this->createMock(MediaObjectRepository::class);
        $this->convertor = new ImagesToMediaObjectReferencesConvertor($this->mediaObjectRepository);
    }

    /**
     * @dataProvider imageDataProvider
     * @test
     */
    public function should_convert_images_to_media_object_references(Images $images, MediaObjectReferences $expectedReferences): void
    {
        foreach ($images->toArray() as $image) {
            $this->mediaObjectRepository
                ->expects($this->exactly(count($images)))
                ->method('load')
                ->willReturnCallback(function ($id) use ($expectedReferences) {
                    foreach ($expectedReferences as $reference) {
                        if (!$reference instanceof MediaObjectReference) {
                            continue;
                        }

                        if ($reference->getMediaObjectId()->toString() === $id) {
                            return MediaObject::create(
                                $reference->getMediaObjectId(),
                                new MIMEType('image/jpg'),
                                new DescriptionProperties($reference->getDescription()->toString()),
                                $reference->getCopyrightHolder(),
                                new Url('http://foo.bar/' . $id . '.jpg'),
                                $reference->getLanguage()
                            );
                        }
                    }
                    return null;
                });
        }

        $result = $this->convertor->convert($images);

        $this->assertCount(count($expectedReferences), $result->toArray());

        foreach ($result->toArray() as $index => $mediaObjectReference) {
            $this->assertEquals($expectedReferences->getByIndex($index), $mediaObjectReference);
        }
    }

    public function imageDataProvider(): array
    {
        $image1Uuid = Uuid::uuid4();
        $image2Uuid = Uuid::uuid4();

        $image1 = new Image(
            $image1Uuid,
            new Language('nl'),
            new Description('Lorum ipsum'),
            new CopyrightHolder('Koen'),
        );

        $image2 = new Image(
            $image2Uuid,
            new Language('nl'),
            new Description('Lorum ipsum'),
            new CopyrightHolder('Koen'),
        );

        return [
            'empty images' => [
                new Images(),
                new MediaObjectReferences(),
            ],
            'single image' => [
                new Images($image1),
                new MediaObjectReferences(
                    ... [$this->givenAnImageWithUuid($image1Uuid)]
                ),
            ],
            'Multiple images' => [
                new Images($image1, $image2),
                new MediaObjectReferences(
                    ... [$this->givenAnImageWithUuid($image1Uuid), $this->givenAnImageWithUuid($image2Uuid)]
                ),
            ],
        ];
    }

    private function givenAnImageWithUuid(Uuid $uuid): MediaObjectReference
    {
        $url = new Url('http://foo.bar/' . $uuid->toString() . '.jpg');

        $mediaObjectDto = new MediaObjectDto(
            $uuid,
            MediaObjectType::imageObject(),
            $url,
            $url,
        );

        return MediaObjectReference::createWithEmbeddedMediaObject(
            $mediaObjectDto,
            new Description('Lorum ipsum'),
            new CopyrightHolder('Koen'),
            new Language('nl')
        );
    }
}
