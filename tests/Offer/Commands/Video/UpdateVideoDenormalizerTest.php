<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Video;

use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

final class UpdateVideoDenormalizerTest extends TestCase
{
    private UpdateVideoDenormalizer $denormalizer;

    public function setUp(): void
    {
        $this->denormalizer = new UpdateVideoDenormalizer();
    }

    /**
     * @test
     * @dataProvider updateVideoProvider
     */
    public function it_should_denormalize(UpdateVideo $updateVideo, array $updateVideoAsArray): void
    {
        $this->assertEquals(
            $updateVideo,
            $this->denormalizer->denormalize($updateVideoAsArray, UpdateVideo::class)
        );
    }

    public function updateVideoProvider(): array
    {
        $updateVideo = new UpdateVideo('208dbe98-ffaa-41cb-9ada-7ec8e0651f48');

        return [
            'updatevideo_with_blank_fields' => [
                $updateVideo->withCopyrightHolder(new CopyrightHolder('publiq')),
                [
                    'id' => '208dbe98-ffaa-41cb-9ada-7ec8e0651f48',
                    'copyrightHolder' => 'publiq',
                ],
            ],
            'updatevideo_with_all_values' => [
                $updateVideo->withCopyrightHolder(new CopyrightHolder('publiq'))
                    ->withLanguage(new Language('fr'))
                    ->withUrl(new Url('https://www.youtube.com/watch?v=123')),
                [
                    'id' => '208dbe98-ffaa-41cb-9ada-7ec8e0651f48',
                    'copyrightHolder' => 'publiq',
                    'language' => 'fr',
                    'url' => 'https://www.youtube.com/watch?v=123',
                ],
            ],
        ];
    }
}
