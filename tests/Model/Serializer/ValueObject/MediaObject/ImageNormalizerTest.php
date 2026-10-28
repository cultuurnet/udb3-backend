<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject;

use Broadway\Repository\Repository;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Media\MediaObject;
use CultuurNet\UDB3\Media\Properties\Description as MediaDescription;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Image;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

final class ImageNormalizerTest extends TestCase
{
    private ImageNormalizer $imageNormalizer;

    protected function setUp(): void
    {
        $mediaRepository = $this->createMock(Repository::class);
        $mediaRepository->method('load')
            ->with('03789a2f-5063-4062-b7cb-95a0a2280d92')
            ->willReturn(
                MediaObject::create(
                    new Uuid('03789a2f-5063-4062-b7cb-95a0a2280d92'),
                    MIMEType::fromSubtype('jpeg'),
                    new MediaDescription('Uploaded image'),
                    new CopyrightHolder('publiq'),
                    new Url('https://images.uitdatabank.be/03789a2f-5063-4062-b7cb-95a0a2280d92.jpg'),
                    new Language('nl')
                )
            );

        $this->imageNormalizer = new ImageNormalizer(
            $mediaRepository,
            new CallableIriGenerator(fn (string $id) => 'https://io.uitdatabank.be/images/' . $id)
        );
    }

    /**
     * @test
     * @dataProvider isAiGeneratedProvider
     */
    public function it_only_normalizes_ai_generated_when_it_has_a_value(?bool $isAiGenerated, array $expected): void
    {
        $image = new Image(
            new Uuid('03789a2f-5063-4062-b7cb-95a0a2280d92'),
            new Language('nl'),
            new Description('Beschrijving'),
            new CopyrightHolder('publiq'),
            $isAiGenerated
        );

        $this->assertEquals($expected, $this->imageNormalizer->normalize($image));
    }

    public function isAiGeneratedProvider(): array
    {
        $normalized = [
            '@id' => 'https://io.uitdatabank.be/images/03789a2f-5063-4062-b7cb-95a0a2280d92',
            '@type' => 'schema:ImageObject',
            'id' => '03789a2f-5063-4062-b7cb-95a0a2280d92',
            'contentUrl' => 'https://images.uitdatabank.be/03789a2f-5063-4062-b7cb-95a0a2280d92.jpg',
            'thumbnailUrl' => 'https://images.uitdatabank.be/03789a2f-5063-4062-b7cb-95a0a2280d92.jpg',
            'description' => 'Beschrijving',
            'copyrightHolder' => 'publiq',
            'inLanguage' => 'nl',
        ];

        return [
            'no isAiGenerated' => [null, $normalized],
            'isAiGenerated true' => [
                true,
                [
                    'contentUrl' => 'https://images.uitdatabank.be/03789a2f-5063-4062-b7cb-95a0a2280d92.ai.jpg',
                    'thumbnailUrl' => 'https://images.uitdatabank.be/03789a2f-5063-4062-b7cb-95a0a2280d92.ai.jpg',
                ] + $normalized + ['isAiGenerated' => true],
            ],
            'isAiGenerated false' => [false, $normalized + ['isAiGenerated' => false]],
        ];
    }

    /**
     * @test
     */
    public function it_denormalizes_ai_generated(): void
    {
        $data = [
            '@id' => 'https://io.uitdatabank.be/images/03789a2f-5063-4062-b7cb-95a0a2280d92',
            'description' => 'Beschrijving',
            'copyrightHolder' => 'publiq',
            'inLanguage' => 'nl',
        ];

        $denormalizer = new ImageDenormalizer();

        $this->assertNull($denormalizer->denormalize($data, Image::class)->isAiGenerated());
        $this->assertTrue($denormalizer->denormalize($data + ['isAiGenerated' => true], Image::class)->isAiGenerated());
    }
}
